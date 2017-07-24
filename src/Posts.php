<?php

namespace WpPwaRegister;

class Posts
{
    use Singleton;

    public function init()
    {
        add_action('init', [$this, 'register']);
        add_action('manage_posts_custom_column', [$this, 'addCustomColumn'], 10, 2);

        add_filter('manage_edit-pwa_users_columns', [$this, 'firebaseUsersManageColumns']);
        add_filter('manage_edit-pwa_notifications_columns', [$this, 'firebaseNotificationsManageColumns']);
    }

    public function register()
    {
        register_post_type('pwa_users', [
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
            ]
        ]);

        register_post_type( 'pwa_notifications', [
            'label' => 'PUSH通知',
            'labels' => [
                'name' => 'PUSH通知',
                'singular_name' => 'PUSH通知',
            ],
            'description' => 'PUSH通知の登録',
            'public' => false,
            'show_in_rest' => true,
            'show_ui' => true,
            'supports' => [
                'title',
                'custom-fields'
            ]
        ] );
    }

    public function firebaseUsersManageColumns($columns)
    {
        $columns['_did_not_reach'] = '非到達数';
        return $columns;
    }

    public function firebaseNotificationsManageColumns($columns)
    {
        $columns['_reach_number'] = '到達/エラー';
        return $columns;
    }

    public function addCustomColumn($column, $post_id)
    {
        if ($column === '_did_not_reach') {
            echo get_post_meta($post_id, '_did_not_reach', true);
        }

        if ($column === '_reach_number') {
            echo get_post_meta($post_id, '_reach_success', true), ' / ', get_post_meta($post_id, '_reach_error', true);
        }
    }
}