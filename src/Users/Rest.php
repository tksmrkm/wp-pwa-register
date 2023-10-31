<?php

namespace WpPwaRegister\Users;

use WP_Error;
use WP_Query;
use WP_REST_Request;
use WpPwaRegister\Customizer;
use WpPwaRegister\Plugin;

class Rest
{
    const MANAGE_USER_ACTION = 'wp-pwa-register_manage-user';
    const CUSTOMIZER_USE_BACKEND_SERVICE = 'wp-pwa_register_use-backend';
    const CUSTOMIZER_BACKEND_REGISTER_ENDPOINT = 'wp-pwa_register_backend-register-endpoint';
    const CUSTOMIZER_BACKEND_ADMIN_PASSWORD = 'wp-pwa_register_backend-admin-password';

    private Customizer $customizer;

    public function __construct(Customizer $customizer)
    {
        $this->customizer = $customizer;
        add_action('rest_api_init', [$this, 'restApiInit']);
    }

    private function _invalidNumber($page, $limit)
    {
        if (!is_numeric($page)) return true;
        if (!is_numeric($limit)) return true;
        if ((int)$page < 0 || (int)$limit < 0) return true;
        return false;
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

        register_rest_route( 'wp_pwa_register/v1', 'register', [
            'methods' => ['POST'],
            'callback' => [$this, 'manageUserEndpoint'],
            'permission_callback' => '__return_true',
            'args' => [
                'token' => [
                    'default' => null
                ],
                'uid' => [
                    'default' => null
                ],
                'nonce' => [
                    'default' => null
                ]
            ]
        ]);

        register_rest_route( 'wp_pwa_register/v1', 'backend_register', [
            'methods' => ['POST'],
            'callback' => [$this, 'backendRegisterEndpoint'],
            'permission_callback' => [$this, 'checkBackendPermission'],
            'args' => [
                'token' => [
                    'default' => null
                ],
                'uid' => [
                    'default' => null
                ],
                'password' => [
                    'default' => null
                ]
            ]
        ]);
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

    private function registerUser($token, $uid)
    {
        // find user by firebase uid
        $user_exists = new WP_Query([
            'post_type' => Post::POST_SLUG,
            'post_title' => $uid
        ]);

        $insert_post = [
            'meta_input' => [
                'token' => $token
            ]
        ];

        if (count($user_exists->posts) > 0) {
            // get meta value
            $token = get_post_meta($user_exists->posts[0]->ID, 'token', true);

            if ($token === $token) {
                $deleted = get_post_meta($user_exists->posts[0]->ID, 'deleted', true);

                if (!$deleted) {
                    // do nothing

                    return [
                        'msg' => 'already registered'
                    ];
                }
            }

            $insert_post['ID'] = $user_exists->posts[0]->ID;
        }

        $result = wp_insert_post($insert_post);

        return [
            'msg' => $result
        ];
    }

    public function manageUserEndpoint(WP_REST_Request $request)
    {
        if (wp_verify_nonce($request['nonce'], self::MANAGE_USER_ACTION)) {
            $use_backend = $this->customizer->get_theme_mod(self::CUSTOMIZER_USE_BACKEND_SERVICE, false);

            if ($use_backend) {
                return $this->backendRegister($request['token'], $request['uid']);
            }

            return $this->registerUser($request['token'], $request['uid']);
        }

        return [
            'msg' => 'nonce failed'
        ];
    }

    public function backendRegisterEndpoint(WP_REST_Request $request)
    {
        return $this->registerUser($request['token'], $request['uid']);
    }

    public function checkBackendPermission(WP_REST_Request $request)
    {
        $user = wp_authenticate(Plugin::USERNAME, $request['password']);
        return $user? true: false;
    }

    private function backendRegister($token, $uid)
    {
        $password = $this->customizer->get_theme_mod(self::CUSTOMIZER_BACKEND_ADMIN_PASSWORD, false);
        $endpoint = $this->customizer->get_theme_mod(self::CUSTOMIZER_BACKEND_REGISTER_ENDPOINT, false);

        if (!$password || !$endpoint) {
            return [
                'msg' => 'prepare backend settings or use self host registration'
            ];
        }

        $ch = curl_init();
        $headers = [
            'TTL: 60',
            'Content-Type: application/json',
        ];
        $body = [
            'token' => $token,
            'uid' => $uid,
            'password' => $password
        ];
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_URL, $endpoint);

        $response = curl_exec($ch);
        curl_close($ch);
    }
}
