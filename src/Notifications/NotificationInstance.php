<?php

namespace WpPwaRegister\Notifications;

use WP_Post;

class NotificationInstance
{
    const FCM_SERVER = 'https://fcm.googleapis.com/fcm/send';
    const POST_KEY = 'notificationinstance';
    const PUBLISHED_FLAG_KEY = '_published_ever';
    const MOD_REMAINDER_KEY = 'mod_remainder';

    private $logs;
    private $customizer;
    private $firebase_server_key;
    private $delete_flag = true;
    private $start_time;
    private $duplicated = [
        'posts' => [],
        'meta' => []
    ];

    public function __construct($logs, $customizer)
    {
        $this->logs = $logs;
        $this->customizer = $customizer;

        add_action('init', [$this, 'init']);
        add_action('publish_pwa_notifications', [$this, 'publish']);
    }

    public function init()
    {
        register_post_type(self::POST_KEY, [
            'label' => 'PUSH分割実体',
            'public' => false,
            'show_in_rest' => false,
        ]);
    }

    private function update($post_id, $reduced_retval)
    {
        update_post_meta($post_id, self::PUBLISHED_FLAG_KEY, true);
        update_post_meta($post_id, '_reach_success', $reduced_retval['success']);
        update_post_meta($post_id, '_reach_error', $reduced_retval['failure']);

        /**
         * Registration ID update
         */
        foreach ($reduced_retval['update_list'] as $user) {
            update_post_meta($user['id'], 'token', $user['registration_id']);
        }
    }

    private function getDeletionList($list)
    {
        $limit = (int)$this->customizer->get_theme_mod('deletion-limit', 0);
        $this->logs->debug($list, $limit);

        if ($limit > 0) {
            $filtered = array_filter(
                $list,
                function($_, $index) use ($limit) {
                    return $index < $limit;
                },
                ARRAY_FILTER_USE_BOTH
            );

            return array_values($filtered);
        }

        return $list;
    }

    private function delete($post_id, $list)
    {
        $reduced_deletion_list = array_merge([], $list, $this->duplicated['posts']);

        /**
         * NotRegistered deletion
         */
        $deletion_list = $this->getDeletionList($reduced_deletion_list);

        $max_execution_time = (int)ini_get('max_execution_time') ?? 30;
        $delete_result = [];

        $this->logs->debug($deletion_list);
        $hard_delete_flag = $this->customizer->get_theme_mod('enable-hard-deletion', false);

        foreach ($deletion_list as $id) {
            set_time_limit($max_execution_time);

            if ($hard_delete_flag) {
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
        update_post_meta($post_id, '_reach_deleted', $deleted_count);
    }

    public function publish($post_id)
    {
        $start = microtime(true);
        $this->start_time = $start;

        $this->logs->debug([
            'publish_start',
            microtime(true) - $start
        ]);

        $had_ever = get_post_meta($post_id, self::PUBLISHED_FLAG_KEY, true);

        $this->logs->debug($had_ever);

        if (!$had_ever) {
            $retval = $this->sendMessage($post_id);

            $reduced_retval = array_reduce($retval, [$this, 'reducer']);

            $this->logs->debug([
                'after sendMessage()',
                microtime(true) - $start
            ]);

            $this->update($post_id, $reduced_retval);

            if ($this->delete_flag) {
                $this->delete($post_id, $reduced_retval['delete_list']);
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
        $mod_base = $this->customizer->get_theme_mod('mod-base');
        $mod_remainder = get_post_meta($post_id, self::MOD_REMAINDER_KEY, true);
        $this->firebase_server_key = $this->customizer->get_theme_mod('server-key');

        foreach ($this->getUsers($mod_base, $mod_remainder) as $users) {
            $this->logs->debug([
                'sending messages',
                microtime(true) - $this->start_time
            ]);

            set_time_limit($max_execution_time);
            $retval[] = $this->curl($users, $post_id, $is_dry);
        }

        return $retval;
    }

    private function getUsers($mod_base, $mod_remainder)
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
                AND
                Post.ID % {$mod_base} = {$mod_remainder}
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
}
