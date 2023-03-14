<?php

namespace WpPwaRegister;

class Posts
{
    use traits\Singleton;

    public function init()
    {
        add_action('init', [$this, 'register']);
        add_action('manage_posts_custom_column', [$this, 'addCustomColumn'], 10, 2);

        add_filter('manage_edit-pwa_notifications_columns', [$this, 'firebaseNotificationsManageColumns']);
    }

    public function register()
    {
        $this->registerPwaUsers();
        $this->registerPwaNotifications();
    }

    private function registerPwaUsers()
    {
        $cap = 'manage_pwa_users';
        $admin = get_role('administrator');
        $admin->add_cap($cap);
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
            ],
            'capabilities' => [
                'read_post' => 'read',
                'read_private_posts' => $cap,
                'edit_post' => $cap,
                'edit_posts' => $cap,
                'edit_others_posts' => 'edit_others_posts',
                'delete_post' => 'delete_others_posts',
                'delete_posts' => 'delete_others_posts',
                'publish_posts' => 'publish_posts',
            ]
        ]);
    }

    private function registerPwaNotifications()
    {
        register_post_type( 'pwa_notifications', [
            'label' => 'PUSH通知',
            'labels' => [
                'name' => 'PUSH通知',
                'singular_name' => 'PUSH通知',
            ],
            'description' => 'PUSH通知の登録',
            'public' => false,
            'rewrite' => false,
            'show_in_rest' => true,
            'show_ui' => true,
            'supports' => [
                'title',
                'custom-fields'
            ],
            'capabilities' => [
                'read_post' => 'read',
                'edit_post' => 'edit_others_posts',
                'edit_posts' => 'edit_others_posts',
                'delete_post' => 'delete_others_posts',
                'delete_posts' => 'delete_others_posts',
                'edit_others_posts' => 'edit_others_posts',
                'publish_posts' => 'publish_posts',
                'read_private_posts' => 'read_private_posts',
            ]
        ] );
    }

    public function firebaseNotificationsManageColumns($columns)
    {
        $columns['_reach_result'] = '送信 / エラー / 削除';

        return $columns;
    }

    public function addCustomColumn($column, $post_id)
    {
        if ($column === '_reach_result') {
            $success = get_post_meta($post_id, '_reach_success', true) ?: '-';
            $error = get_post_meta($post_id, '_reach_error', true) ?: '-';
            $deleted = get_post_meta($post_id, '_reach_deleted', true) ?: '-';

            echo "${success} / ${error} / ${deleted}";
        }
    }
}