<?php

namespace WpPwaRegister\Notifications;

use WP_HTTP_Response;
use WP_Post;
use WP_Query;
use WP_REST_Request;
use WP_REST_Server;
use WpPwaRegister\Logs;
use WpPwaRegister\traits\Singleton;
use WpPwaRegister\Notifications\Post;

class Notifications
{
    use Singleton;

    const PROCESSED_KEY = '_processed';

    private $firebase_server_key;
    private $customizer;
    private Logs $logs;
    private $delete_flag = true;
    private $deletion_limit = 0;
    private $split_transfer = 1;
    private $split_tick = 180;
    private $hard_delete_flag = false;
    private $duplicated = [
        'posts' => [],
        'meta' => []
    ];
    private $start_time;

    public function init($container)
    {
        $this->customizer = $container['customizer'];
        $this->logs = $container['logs'];
        $this->firebase_server_key = $this->customizer->get_theme_mod('server-key');
        $this->delete_flag = $this->customizer->get_theme_mod('enable-deletion', true);
        $this->hard_delete_flag = $this->customizer->get_theme_mod('enable-hard-deletion', false);
        $this->split_transfer = $this->customizer->get_theme_mod('split-transfer', $this->split_transfer);
        $this->split_tick = $this->customizer->get_theme_mod('split-tick', $this->split_tick);

        add_action('wp_insert_post', [$this, 'wpInsertPost']);
        add_action('rest_api_init', [$this, 'restApiInit']);
        add_action('save_post', [$this, 'postSave'], 10, 3);
        add_action('trash_' . Post::POST_SLUG, [$this, 'trashPost'], 10, 2);
        add_filter('rest_post_dispatch', [$this, 'restPostDispatch'], 10, 3);

        $pattern = "/^\/wp-json\/wp\/v2\/pwa_notifications\/.+$/";
        if (preg_match($pattern, $_SERVER['REQUEST_URI'])) {
            $this->sendHeaders();
        }
    }

    public function restPostDispatch(WP_HTTP_Response $response, WP_REST_Server $server, WP_REST_Request $request)
    {
        $route = $request->get_route();
        $slug = $this->customizer->get_theme_mod('notifications_fallback_slug', 'pwa_notifications');
        $matched = preg_match('/^\/wp\/v2\/' . $slug . '/', $route);
        if ($matched && $response->data["data"]["status"] === 404) {
            $id = preg_replace('/.*\/(\d+)$/', '$1', $route);$
            $post = new WP_Query(['p' => $id, 'post_type' => NotificationHttpV1::POST_SLUG]);

            if ($post->have_posts()) {
                $headline = get_post_meta($id, NotificationHttpV1::META_HEADLINE, true);
                $icon = get_post_meta($id, NotificationHttpV1::META_ICON, true);
                $link = get_post_meta($id, NotificationHttpV1::META_LINK, true);
                $response->set_status(200);
                $response->set_data([
                    'title' => [
                        'rendered' => $post->post->post_title
                    ],
                    'post_meta' => [
                        'headline' => $headline,
                        'icon' => $icon,
                        'link' => $link,
                    ],
                ]);
            }
        }
        return $response;
    }

    public function trashPost($post_id, $post)
    {
        $this->logs->debug($post_id, $post->post_type, Post::POST_SLUG);

        if ($post->post_type === Post::POST_SLUG) {
            $ids = get_post_meta($post_id, '_' . NotificationInstance::POST_KEY, true);
            $ids = explode(',', $ids);

            $this->logs->debug($ids);

            foreach ($ids as $id) {
                wp_trash_post($id);
            }
        }
    }

    public function sendHeaders()
    {
        $maxage = $this->customizer->get_theme_mod('notifications-s-maxage');
        header('Cache-Control: s-maxage=' . $maxage);
    }

    public function reducer($prev, $current)
    {
        return [
            'update_list' => array_merge($prev['update_list'] ?? [], $current['update_list']),
            'success' => ($prev['success'] ?? 0) + $current['success'],
            'failure' => ($prev['failure'] ?? 0) + $current['failure'],
            'delete_list' => array_merge($prev['delete_list'] ?? [], $current['delete_list']),
        ];
    }

    public function deletionListFilter($item, $index)
    {
        return $index < $this->deletion_limit;
    }

    public function restApiInit()
    {
        register_rest_field('pwa_notifications', 'post_meta', [
            'get_callback' => function($object, $field_name, $request) {
                $meta_fields = [
                    'link',
                    'headline',
                    'icon'
                ];
                $meta = [];
                foreach ($meta_fields as $key) {
                    $meta[$key] = get_post_meta($object['id'], $key, true);
                }
                return $meta;
            },
            'update_callback' => function($value, $post, $field_name) {
                if (!$value) {
                    return;
                }

                $meta_keys = get_post_meta($post->ID, '_' . NotificationInstance::POST_KEY, true);
                $instances = explode(',', $meta_keys);
                $filtered = array_filter($instances, function($id) {
                    return strlen($id) > 0;
                });
                $instance_ids = array_map(function($id) {
                    return trim($id);
                }, $filtered);
                // <-

                foreach ($value as $key => $data) {
                    if (is_array($data)) {
                        foreach ($data as $record) {
                            add_post_meta($post->ID, $key, $record);

                            foreach ($instance_ids as $id) {
                                add_post_meta($id, $key, $record);
                            }
                        }
                    } else {
                        add_post_meta($post->ID, $key, $data);

                        foreach ($instance_ids as $id) {
                            add_post_meta($id, $key, $data);
                        }
                    }
                }
            }
        ]);
    }

    public function wpInsertPost($post_id)
    {
        if (isset($_GET['post_type']) && $_GET['post_type'] === 'pwa_notifications') {
            add_post_meta($post_id, 'link', '', true);
            add_post_meta($post_id, 'headline', '', true);
            add_post_meta($post_id, 'icon', '', true);
        }
    }

    /**
     * save_post action hook
     */
    public function postSave($post_id, WP_Post $post, $updated)
    {
        if ($post->post_type !== Post::POST_SLUG) {
            return true;
        }

        if (
            $post->post_status !== 'publish'
            && $post->post_status !== 'future'
        ) {
            return true;
        }

        // セット済みフラグを取得
        $processed = get_post_meta($post_id, self::PROCESSED_KEY, true);
        $step = $this->customizer->get_theme_mod('split-tick') ?? 180;

        if (!$processed) {
            // セット済みフラグを保存
            add_post_meta($post_id, self::PROCESSED_KEY, true, true);

            $mod_base = $this->customizer->get_theme_mod('split-transfer');

            $model = [
                'post_type' => NotificationInstance::POST_KEY,
                'post_title' => $post->post_title,
                'post_date' => $post->post_date,
            ];

            $inserted_post_ids = [];

            for ($i = 0; $i < (int)$mod_base; $i++) {
                $status = $i === 0 ? $post->post_status: 'future';

                $inserted_post_ids[] = wp_insert_post(
                    array_merge([], $model, [
                        'post_date' => date('Y-m-d H:i:s', strtotime($post->post_date) + ($i * $step)),
                        'post_status' => $status
                    ])
                    , true
                );
            }

            $meta_headline = get_post_meta($post_id, 'headline', true);
            $meta_icon = get_post_meta($post_id, 'icon', true);
            $meta_link = get_post_meta($post_id, 'link', true);

            $this->logs->debug($inserted_post_ids, $mod_base, $step, [$post_id, '_' . NotificationInstance::POST_KEY, implode(',', $inserted_post_ids), true]);

            add_post_meta($post_id, '_' . NotificationInstance::POST_KEY, implode(',', $inserted_post_ids), true);

            foreach ($inserted_post_ids as $key => $id) {
                add_post_meta($id, 'headline', $meta_headline, true);
                add_post_meta($id, 'icon', $meta_icon, true);
                add_post_meta($id, 'link', $meta_link, true);
                add_post_meta($id, NotificationInstance::MOD_REMAINDER_KEY, (string)$key, true);
                add_post_meta($id, NotificationInstance::PARENT_KEY, $post_id, true);
            }
        } else {
            // update
            if ($post->post_status === 'future') {
                $children = get_post_meta($post_id, '_' . NotificationInstance::POST_KEY, true);
                $children = explode(',', $children);

                $new_headline = get_post_meta($post_id, 'headline', true);
                $new_icon = get_post_meta($post_id, 'icon', true);
                $new_link = get_post_meta($post_id, 'link', true);

                foreach ($children as $child) {
                    $remainder = (int)get_post_meta($child, NotificationInstance::MOD_REMAINDER_KEY, true);
                    $child_post = get_post($child);
                    $diff = $remainder * $step;
                    $child_post->post_date = date('Y-m-d H:i:s', strtotime($post->post_date) + $diff);
                    $child_post->post_title = $post->post_title;
                    wp_update_post($child_post);

                    // 更新処理
                    update_post_meta($child, 'headline', $new_headline);
                    update_post_meta($child, 'icon', $new_icon);
                    update_post_meta($child, 'link', $new_link);
                }
            }
        }
    }
}