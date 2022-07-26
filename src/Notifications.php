<?php

namespace WpPwaRegister;

use WP_Query;

class Notifications
{
    use traits\Singleton;
    
    const FCM_SERVER = 'https://fcm.googleapis.com/fcm/send';

    private $firebase_server_key;

    public function init($container)
    {
        $this->customizer = $container['customizer'];
        $this->logs = $container['logs'];
        $this->firebase_server_key = $this->customizer->get_theme_mod('server-key');
        add_action('wp_insert_post', [$this, 'wpInsertPost']);
        add_action('rest_api_init', [$this, 'restApiInit']);
        add_action('publish_pwa_notifications', [$this, 'publish']);

        $pattern = "/^\/wp-json\/wp\/v2\/pwa_notifications\/.+$/";
        if (preg_match($pattern, $_SERVER['REQUEST_URI'])) {
            $this->sendHeaders();
        }
    }

    public function sendHeaders()
    {
        $maxage = $this->customizer->get_theme_mod('notifications-s-maxage');
        header('Cache-Control: s-maxage=' . $maxage);
    }

    public function publish($post_id)
    {
        $start = microtime(true);

        $this->logs->debug([
            'publish_start',
            microtime(true) - $start
        ]);

        $had_ever = get_post_meta($post_id, '_published_ever', true);

        if (!$had_ever) {
            $error = $this->sendMessage($post_id);
            $this->logs->debug([
                'after sendMessage()',
                microtime(true) - $start
            ]);

            update_post_meta($post_id, '_published_ever', true);
            update_post_meta($post_id, '_reach_success', wp_count_posts('pwa_users')->draft - $error);
            update_post_meta($post_id, '_reach_error', $error);
        }

        $this->logs->debug([
            'publish_finish',
            microtime(true) - $start
        ]);
    }

    private function sendMessage($post_id)
    {
        $error = 0;
        $max_execution_time = (int)ini_get('max_execution_time') ?? 30;

        foreach ($this->getUsers() as $users) {
            set_time_limit($max_execution_time);
            $error += $this->curl($users, $post_id);
        }

        return $error;
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
            $query = "SELECT Post.ID as id, Meta.meta_value as token FROM {$wpdb->postmeta} as `Meta` INNER JOIN {$wpdb->posts} as `Post` ON Meta.post_id = Post.ID WHERE Meta.meta_key = 'token' AND Post.post_type = 'pwa_users' AND Post.post_status = 'draft' ORDER BY Post.ID DESC LIMIT {$limit} OFFSET {$offset}";
            $users = $wpdb->get_results($query);
            if (count($users)) {
                $retval = [
                    'endpoints' => [],
                    'ids' => []
                ];
                foreach ($users as $user) {
                    if (!in_array($user->id, $sent_list)) {
                        $retval['endpoints'][] = $user->token;
                        $retval['ids'][] = $user->id;
                        $sent_list[] = $user->id;
                    }
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

        if (!$dry) {
            foreach ($response->results as $key => $result) {
                if (isset($result->registration_id)) {
                    update_post_meta($ids['ids'][$key], 'token', $result->registration_id);
                }

                if (isset($result->error)) {
                    if (preg_match('/NotRegistered/', $result->error)) {
                        wp_delete_post($ids['ids'][$key]);
                    }
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
            },
            'update_callback' => function($value, $post, $field_name) {
                if (!$value) {
                    return;
                }

                foreach ($value as $key => $data) {
                    if (is_array($data)) {
                        foreach ($data as $record) {
                            add_post_meta($post->ID, $key, $record);
                        }
                    } else {
                        add_post_meta($post->ID, $key, $data);
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
}