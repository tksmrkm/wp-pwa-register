<?php

namespace WpPwaRegister\Notifications;

use WP_Post;
use WpPwaRegister\Logs;
use WpPwaRegister\traits\Singleton;
use WpPwaRegister\Notifications\Post;

class Notifications
{
    use Singleton;

    const FCM_SERVER = 'https://fcm.googleapis.com/fcm/send';
    const META_PSEUDO_KEY = '_pseudo';
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
        add_action('publish_pwa_notifications', [$this, 'publish']);
        add_action('trash_' . Post::POST_SLUG, [$this, 'trashPost'], 10, 2);

        $pattern = "/^\/wp-json\/wp\/v2\/pwa_notifications\/.+$/";
        if (preg_match($pattern, $_SERVER['REQUEST_URI'])) {
            $this->sendHeaders();
        }
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

    private function getDeletionList($list)
    {
        $this->logs->debug($list, $this->deletion_limit);

        if ($this->deletion_limit > 0) {
            $filtered = array_filter($list, [$this, 'deletionListFilter'], ARRAY_FILTER_USE_BOTH);

            return array_values($filtered);
        }

        return $list;
    }

    public function publish($post_id)
    {
        $pseudo = get_post_meta($post_id, self::META_PSEUDO_KEY, true);

        if ($pseudo) {
            $this->logs->debug('Skip pseudo post');
            return false;
        }

        $start = microtime(true);
        $this->start_time = $start;

        $this->logs->debug([
            'publish_start',
            microtime(true) - $start
        ]);

        $had_ever = get_post_meta($post_id, '_published_ever', true);

        if (!$had_ever) {
            $retval = $this->sendMessage($post_id);

            /**
             * @param update_list {id: string, registration_id: string}[]
             * @param success number
             * @param failure number
             * @param delete_list id[]
             */
            $reduced_retval = array_reduce($retval, [$this, 'reducer']);
            $reduced_deletion_list = array_merge([], $reduced_retval['delete_list'], $this->duplicated['posts']);

            $this->logs->debug([
                'after sendMessage()',
                microtime(true) - $start
            ]);

            update_post_meta($post_id, '_published_ever', true);
            update_post_meta($post_id, Post::REACH_SUCCESS_KEY, $reduced_retval['success']);
            update_post_meta($post_id, Post::REACH_ERROR_KEY, $reduced_retval['failure']);

            /**
             * Registration ID update
             */
            foreach ($reduced_retval['update_list'] as $user) {
                update_post_meta($user['id'], 'token', $user['registration_id']);
            }

            /**
             * NotRegistered deletion
             */
            $this->deletion_limit = (int)$this->customizer->get_theme_mod('deletion-limit', 0);
            $deletion_list = $this->getDeletionList($reduced_deletion_list);

            if ($this->delete_flag) {
                $max_execution_time = (int)ini_get('max_execution_time') ?? 30;
                $delete_result = [];

                $this->logs->debug($deletion_list);
                foreach ($deletion_list as $id) {
                    set_time_limit($max_execution_time);

                    if ($this->hard_delete_flag) {
                        $delete_result[] = wp_delete_post($id);
                    } else {
                        // soft delete
                        $delete_result[] = update_post_meta($id, 'deleted', true);
                    }
                }

                $deleted_count = count(
                    array_filter($delete_result, function($item) {
                        return $item !== false;
                    })
                );

                $this->logs->debug($delete_result, $deleted_count);
                update_post_meta($post_id, Post::REACH_DELETED_KEY, $deleted_count);
            }
        }

        $this->logs->debug([
            'publish_finish',
            microtime(true) - $start
        ]);
    }

    private function sendMessage($post_id)
    {
        $retval = [];
        $max_execution_time = (int)ini_get('max_execution_time') ?? 30;
        $is_dry = $this->customizer->get_theme_mod('enable-dry-mode');

        foreach ($this->getUsers() as $users) {
            $this->logs->debug([
                'sending messages: (is_dry, time)',
                $is_dry,
                microtime(true) - $this->start_time
            ]);

            set_time_limit($max_execution_time);
            $retval[] = $this->curl($users, $post_id, $is_dry);
        }

        return $retval;
    }

    /**
     * Generator
     * @return [
     *   'endpoints' => Token[],
     *   'ids' => pwa_users->id[]
     * ]
     */
    private function getUsers()
    {
        global $wpdb;

        $page = 0;
        $limit = 1000;

        $sent_list = [];

        while ($page >= 0) {
            $offset = $page * $limit;

            $query = <<<QUERY
            SELECT
                Post.ID as id,
                Token.meta_id as meta_id,
                Token.meta_value as token
            FROM
                {$wpdb->posts} as `Post`
            LEFT JOIN
                {$wpdb->postmeta} as `Token`
                    ON Token.post_id = Post.ID
                    AND Token.meta_key = 'token'
            LEFT JOIN
                {$wpdb->postmeta} as `Deleted`
                    ON Deleted.post_id = Post.ID
                    AND Deleted.meta_key = 'deleted'
            WHERE
                Post.post_status = 'draft'
                AND
                Post.post_type = 'pwa_users'
                AND
                Deleted.meta_value IS NULL
                AND
                Token.meta_value IS NOT NULL
            ORDER BY
                Post.ID
            DESC
            LIMIT {$limit}
            OFFSET {$offset}
            QUERY;

            $users = $wpdb->get_results($query);

            if (count($users)) {
                $retval = [
                    'endpoints' => [],
                    'ids' => []
                ];

                foreach ($users as $user) {
                    if (in_array($user->token, $sent_list)) {
                        $this->duplicated['posts'][] = $user->id;
                        $this->duplicated['meta'][] = $user->meta_id;
                        continue;
                    }

                    $retval['endpoints'][] = $user->token;
                    $retval['ids'][] = $user->id;
                    $sent_list[] = $user->token;
                }

                yield $retval;

                $page++;
            } else {
                $page = -1;
            }
        }
    }

    private function curl($ids, $post_id, $dry = false)
    {
        $headers = [
            'TTL: 60',
            'Content-Type: application/json',
            'Authorization: key=' . $this->firebase_server_key
        ];

        $data = [
            'registration_ids' => [],
            'data' => [
                'post_id' => $post_id
            ]
        ];

        if ($dry) {
            $data['dry_run'] = true;
        }

        foreach ($ids['endpoints'] as $endpoint) {
            $data['registration_ids'][] = $endpoint;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_URL, self::FCM_SERVER);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->error_check($response, $ids, $dry);
    }

    private function error_check($response, $ids, $dry)
    {
        $response = json_decode($response);

        $retval = [
            'update_list' => [],
            'success' => $response->success,
            'failure' => $response->failure,
            'delete_list' => []
        ];

        foreach ($response->results as $key => $result) {
            if (isset($result->registration_id)) {
                $retval['update_list'][] = [
                    'id' => $ids['ids'][$key],
                    'registration_id' => $result->registration_id
                ];
            }

            if (isset($result->error)) {
                if (preg_match('/NotRegistered/', $result->error)) {
                    $retval['delete_list'][] = $ids['ids'][$key];
                }
            }
        }

        return $retval;
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

        add_post_meta($post_id, self::META_PSEUDO_KEY, true, true);

        if (
            $post->post_status !== 'publish'
            && $post->post_status !== 'future'
        ) {
            return true;
        }

        // セット済みフラグを取得
        $processed = get_post_meta($post_id, self::PROCESSED_KEY, true);
        $this->logs->debug($processed, $post_id, $post);

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

            $this->logs->debug($inserted_post_ids, $mod_base, $step);

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