<?php

namespace WpPwaRegister;

use WP_Error;
use WP_Query;

class Users
{
    const MANAGE_CAP = 'manage_pwa_users';
    const POST_SLUG = 'pwa_users';
    const MANAGE_CREATED_COLUMN_KEY = 'created_date';
    const QUERY_ROUTE_KEY_FOR_SUBSCRIBE = 'pwa-register-subscribe';
    const META_API_VERSION_KEY = '_api_version';
    const FCM_SERVER = 'https://iid.googleapis.com/iid/v1:batchAdd';

    private $firebase_server_key;

    public function __construct(Customizer $customizer)
    {
        $this->firebase_server_key = $customizer->get_theme_mod('server-key');

        add_action('init', [$this, 'register']);
        add_filter('query_vars', [$this, 'addVars']);
        add_action('parse_request', [$this, 'parseRequest']);
        add_action('rest_api_init', [$this, 'restApiInit']);
        add_action('manage_posts_custom_column', [$this, 'addCustomColumn'], 10, 2);
        add_filter('manage_edit-' . self::POST_SLUG . '_columns', [$this, 'manageColumns']);
        add_filter("manage_edit-" . self::POST_SLUG . "_sortable_columns", [$this, 'sortableColumns']);
    }

    public function registerRoute()
    {
        add_rewrite_rule('^pwa-subscribe$', 'index.php?' . self::QUERY_ROUTE_KEY_FOR_SUBSCRIBE . '=1', 'top');
    }

    public function addVars($vars)
    {
        $vars[] = self::QUERY_ROUTE_KEY_FOR_SUBSCRIBE;
        return $vars;
    }

    public function parseRequest($wp)
    {
        if (isset($wp->query_vars[self::QUERY_ROUTE_KEY_FOR_SUBSCRIBE])) {
            if ($wp->query_vars[self::QUERY_ROUTE_KEY_FOR_SUBSCRIBE] === '1') {
                $this->subscribe();
                exit;
            }
        }
    }

    private function subscribe()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            var_dump('THIS IS GET');
            return false;
        }
        $headers = [
            'Authorization: key=' . $this->firebase_server_key,
            'Content-Type: application/json'
        ];

        $data = [
            'to' => '/topics/all',
            'registration_tokens' => [$_POST['token']],
            'priority' => 'high'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_URL, self::FCM_SERVER);
        $response = curl_exec($ch);

        $error = curl_error($ch);
        $errno = curl_errno($ch);

        curl_close($ch);

        // wp create post
        $user = $this->manageUser($_POST['uid'], $_POST['token']);

        header('Content-Type: application/json');
        echo json_encode([
            'result' => $response,
            'curl' => [
                'error' => $error,
                'errno' => $errno
            ],
            'user' => $user
        ]);

        exit;
    }

    private function manageUser($uid, $token)
    {
        $query = new WP_Query(['title' => $uid]);

        if ($query->have_posts()) {
            $status = 'updated';
            $id = $query->post->ID;
        } else {
            $status = 'generated';
            $id = wp_insert_post([
                'post_title' => $uid,
                'post_type' => self::POST_SLUG
            ]);
        }

        update_post_meta($id, 'token', $token);
        update_post_meta($id, self::META_API_VERSION_KEY, 'v2');

        return [
            'status' => $status,
            'id' => $id
        ];
    }

    public function register()
    {
        $this->registerRoute();

        $admin = get_role('administrator');
        $admin->add_cap(self::MANAGE_CAP);

        register_post_type(Users::POST_SLUG, [
            'label' => 'pwa_users',
            'labels' => [
                'name' => 'pwa_users',
                'singular_name' => 'pwa_users'
            ],
            'public' => true,
            'show_in_rest' => true,
            'show_ui' => true,
            'supports' => [
                'title',
                'custom-fields'
            ],
            'capabilities' => [
                'read_post' => 'read',
                'read_private_posts' => self::MANAGE_CAP,
                'edit_post' => self::MANAGE_CAP,
                'edit_posts' => self::MANAGE_CAP,
                'edit_others_posts' => 'edit_others_posts',
                'delete_post' => 'delete_others_posts',
                'delete_posts' => 'delete_others_posts',
                'publish_posts' => 'publish_posts',
            ]
        ]);
    }

    public function sortableColumns($columns)
    {
        $columns[self::MANAGE_CREATED_COLUMN_KEY] = 'date';
        return $columns;
    }

    public function manageColumns($columns)
    {
        $columns[self::MANAGE_CREATED_COLUMN_KEY] = '作成日時';

        return $columns;
    }

    public function addCustomColumn($column, $post_id)
    {
        if ($column === self::MANAGE_CREATED_COLUMN_KEY) {
            $post = get_post($post_id);
            echo $post->post_date;
        }
    }

    public function restApiInit()
    {
        register_rest_route( 'wp_pwa_register/v1', 'notification_ids', [
            'methods' => ['GET'],
            'callback' => [$this, 'pwaNotificationIds'],
            'permission_callback' => '__return_true',
            'args' => [
                'page' => [
                    'default' => 1
                ],
                'limit' => [
                    'default' => 100
                ]
            ]
        ]);

        register_rest_field('pwa_users', 'token', [
            'update_callback' => function($value, $object, $field_name) {
                update_post_meta($object->ID, 'token', $value);

                // 再登録時に論理削除フラグを強制削除
                delete_post_meta($object->ID, 'deleted');
            },
            'get_callback' => function($object, $field_name) {
                return get_post_meta($object['id'], $field_name, true);
            }
        ]);

        register_rest_field('pwa_users', 'deleted', [
            'get_callback' => function($object, $field_name) {
                return get_post_meta($object['id'], 'deleted', true) ? true: false;
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
