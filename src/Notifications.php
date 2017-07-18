<?php

namespace WpPwaRegister;

use WP_Query;

class Notifications
{
    use Singleton;

    const FCM_SERVER = 'https://fcm.googleapis.com/fcm/send';

    private $firebase_server_key;

    public function init($container)
    {
        $this->customizer = $container['customizer'];
        $this->firebase_server_key = $this->customizer->get_theme_mod('server-key');
        add_action('wp_insert_post', [$this, 'wpInsertPost']);
        add_action('rest_api_init', [$this, 'restApiInit']);
        add_action('publish_pwa_notifications', [$this, 'publish']);
    }

    public function publish($post_id)
    {
        $had_ever = get_post_meta($post_id, '_published_ever', true);

        if (!$had_ever) {
            $pwa_users = new WP_Query([
                'post_type' => 'pwa_users',
                'post_status' => 'any',
                'posts_per_page' => -1,
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key' => '_did_not_reach',
                        'value' => 'lorem',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key' => '_did_not_reach',
                        'value' => '5',
                        'compare' => '<='
                    ]
                ]
            ]);

            $error = $this->sendMessage($pwa_users, $post_id);

            update_post_meta($post_id, '_published_ever', true);
            update_post_meta($post_id, '_reach_success', $pwa_users->post_count - $error);
            update_post_meta($post_id, '_reach_error', $error);
        }
    }

    private function sendMessage($users, $post_id)
    {
        $ids = [
            'endpoints' => [],
            'ids' => []
        ];

        foreach ($users->posts as $user) {
            $content = json_decode($user->post_content);
            if (isset($content->token)) {
                $ids['endpoints'][] = $content->token;
                $ids['ids'][] = $user->post_title;
            }
        }

        return $this->curl($ids, $post_id);
    }

    private function curl($ids, $post_id)
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

        foreach ($ids['endpoints'] as $endpoint) {
            $data['registration_ids'][] = $endpoint;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_URL, self::FCM_SERVER);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->error_check($response, $ids);
    }

    private function error_check($response, $ids)
    {
        $response = json_decode($response);

        foreach ($response->results as $key => $result) {
            if (isset($result->registration_id)) {
                wp_update_post([
                    'ID' => $ids['id'][$key],
                    'post_title' => $result->registration_id
                ]);
            }

            if (isset($result->error)) {
                if (preg_match('/NotRegistered/', $result->error)) {
                    wp_delete_post($ids['id'][$key]);
                }
            }
        }

        return $response->failure;
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
}