<?php

namespace WpPwaRegister;

use WP_Error;

class Users
{
    use traits\Singleton;
    
    public function init()
    {
        add_action('rest_api_init', [$this, 'restApiInit']);
    }

    public function restApiInit()
    {
        register_rest_route( 'wp_pwa_register/v1', 'notification_ids', [
            'methods' => ['GET'],
            'callback' => [$this, 'pwaNotificationIds'],
            'args' => [
                'page' => 1,
                'limit' => 100
            ]
        ]);

        register_rest_field('pwa_users', 'token', [
            'update_callback' => function($value, $object, $field_name) {
                update_post_meta($object->ID, 'token', $value);
            }
        ]);
    }

    private function _invalidNumber($page, $limit)
    {
        if (!is_numeric($page)) return true;
        if (!is_numeric($limit)) return true;
        if ((int)$page < 0 || (int)$limit < 0) return true;
        return false;
    }

    public function pwaNotificationIds()
    {
        global $wpdb;
        $page = isset($_GET['page']) ? $_GET['page']: 1;
        $limit = isset($_GET['limit']) ? $_GET['limit']: 100;

        /**
         * both query MUST numeric value
         * @param page
         * @param limit
         */
        if ($this->_invalidNumber($page, $limit)) {
            return new WP_Error(
                'wp_pwa_register/invalid_param',
                'both page, limit queries must numeric value',
                [
                    'status' => 400,
                    'page' => $page,
                    'limit' => $limit
                ]
            );
        }

        $page = (int)$page;
        $limit = (int)$limit;

        $offset = ($page - 1) * $limit;

$query = <<<QUERY
SELECT
    Post.ID as id,
    Meta.meta_value as token
FROM
    {$wpdb->postmeta} as `Meta`
INNER JOIN
    {$wpdb->posts} as `Post`
    ON
    Meta.post_id = Post.id
WHERE
    Meta.meta_key = 'token'
    AND
    Post.post_type = 'pwa_users'
ORDER BY
    Post.ID
    DESC
LIMIT {$limit}
OFFSET {$offset}
;
QUERY
;

        $pwa_users = $wpdb->get_results($query);
        $chunks = array_chunk($pwa_users, 500);
        $mapped = array_map(function($chunk) {
            $retval = [
                "endpoints" => [],
                "ids" => []
            ];
            foreach ($chunk as $user) {
                $retval["endpoints"][] = $user->token;
                $retval["ids"][] = $user->id;
            }
            return $retval;
        }, $chunks);

        return [
            'data' => $mapped,
            'meta' => [
                'offset' => $offset,
                'limit' => $limit,
                'page' => $page
            ]
        ];
    }
}