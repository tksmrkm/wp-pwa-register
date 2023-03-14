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

    private $columns = [
        [
            'key' => '_reach_success',
            'label' => '送信',
            'default' => 0
        ],
        [
            'key' => '_reach_error',
            'label' => 'エラー',
            'default' => 0
        ],
        [
            'key' => '_reach_deleted',
            'label' => '削除',
            'default' => 0
        ],
    ];

    public function firebaseNotificationsManageColumns($columns)
    {
        foreach ($this->columns as $column) {
            $columns[$column['key']] = $column['label'];
        }
        return $columns;
    }

    public function addCustomColumn($column, $post_id)
    {
        $filtered = array_filter($this->columns, function($_column) use ($column) {
            return $column === $_column['key'];
        });

        if (count($filtered)) {
            $meta_column = array_pop($filtered);
            $meta = get_post_meta($post_id, $meta_column['key'], true);
            echo $meta;
        }
    }
}