<?php

namespace WpPwaRegister\Notifications;

use WP_REST_Response;

class Rest
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'restApiInit']);
    }

    public function restApiInit()
    {
        register_rest_route('wp_pwa_register/v1', 'update_result', [
            'callback' => [$this, 'updateResultCallback'],
            'methods' => ['POST'],
            'permission_callback' => [$this, 'updateResultPermission'],
            'args' => [
                'id' => [
                    'default' => 0
                ],
                'reached' => [
                    'default' => 0
                ],
                'error' => [
                    'default' => 0
                ],
                'deleted' => [
                    'default' => 0
                ],
                'delete_ids' => [
                    'default' => []
                ]
            ]
        ]);
    }

    public function updateResultCallback($req)
    {
        $params = json_decode($req->get_body());

        $reached = get_post_meta($params->id, Post::REACH_SUCCESS_KEY, true);
        $error = get_post_meta($params->id, Post::REACH_ERROR_KEY, true);
        $deleted = get_post_meta($params->id, Post::REACH_DELETED_KEY, true);

        update_post_meta($params->id, Post::REACH_SUCCESS_KEY, $reached + $params->reached, $reached);
        update_post_meta($params->id, Post::REACH_ERROR_KEY, $error + $params->error, $error);
        update_post_meta($params->id, Post::REACH_DELETED_KEY, $deleted + count($params->delete_ids), $deleted);

        foreach ($params->delete_ids as $id) {
            update_post_meta($id, 'deleted', true);
        }

        return new WP_REST_Response([
            'msg' => 'done'
        ], 200);
    }

    public function updateResultPermission()
    {
        return current_user_can('publish_posts');
    }
}