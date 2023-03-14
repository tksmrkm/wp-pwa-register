<?php

namespace WpPwaRegister;

use Exception;

class Notifications
{
    use traits\Singleton;
    
    const FCM_SERVER = 'https://fcm.googleapis.com/fcm/send';

    private $firebase_server_key;
    private $customizer;
    private $logs;
    private $delete_flag = true;
    private $hard_delete_flag = false;
    private $duplicated = [
        'posts' => [],
        'meta' => []
    ];

    public function init($container)
    {
        $this->customizer = $container['customizer'];
        $this->logs = $container['logs'];
        $this->firebase_server_key = $this->customizer->get_theme_mod('server-key');
        $this->delete_flag = $this->customizer->get_theme_mod('enable-deletion', true);
        $this->hard_delete_flag = $this->customizer->get_theme_mod('enable-hard-deletion', false);

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

    public function reducer($prev, $current)
    {
        return [
            'update_list' => array_merge($prev['update_list'] ?? [], $current['update_list']),
            'success' => ($prev['success'] ?? 0) + $current['success'],
            'failure' => ($prev['failure'] ?? 0) + $current['failure'],
            'delete_list' => array_merge($prev['delete_list'] ?? [], $current['delete_list']),
        ];
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
            $retval = $this->sendMessage($post_id);

            /**
             * @param update_list {id: string, registration_id: string}[]
             * @param success number
             * @param failure number
             * @param delete_list id[]
             */
            $reduced_retval = array_reduce($retval, [$this, 'reducer']);
            $reduced_deletion_list = array_merge([], $reduced_retval['delete_list'], $this->duplicated['posts']);

            $this->logs->debug($reduced_retval);

            $this->logs->debug([
                'after sendMessage()',
                microtime(true) - $start
            ]);

            update_post_meta($post_id, '_published_ever', true);
            update_post_meta($post_id, '_reach_success', $reduced_retval['success']);
            update_post_meta($post_id, '_reach_error', $reduced_retval['failure']);

            /**
             * Registration ID update
             */
            foreach ($reduced_retval['update_list'] as $user) {
                update_post_meta($user['id'], 'token', $user['registration_id']);
            }

            /**
             * NotRegistered deletion
             */
            $deletion_limit = (int)$this->customizer->get_theme_mod('deletion-limit', 0);
            $deletion_list = $deletion_limit > 0 ? array_filter($reduced_deletion_list, function($item, $index) use ($deletion_limit) {
                return $index < $deletion_limit;
            }): $reduced_deletion_list;

            $this->logs->debug($deletion_limit, $deletion_list);
            $this->logs->debug($this->delete_flag, $this->hard_delete_flag);

            if ($this->delete_flag) {
                $max_execution_time = (int)ini_get('max_execution_time') ?? 30;
                $delete_result = [];

                foreach ($deletion_list as $index => $id) {
                    set_time_limit($max_execution_time);

                    $this->logs->debug($index, $id);

                    if ($this->hard_delete_flag) {
                        $delete_result[] = wp_delete_post($id);
                    } else {
                        // soft delete
                        $delete_result[] = update_post_meta($id, 'deleted', true);
                    }
                }

                $this->logs->debug($delete_result);
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

        $this->logs->debug([
            'sent' => count($sent_list),
            'duplicated' => $this->duplicated
        ]);
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