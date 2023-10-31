<?php

namespace WpPwaRegister\Users;

class Post
{
    const MANAGE_CAP = 'manage_pwa_users';
    const MANAGE_CREATED_COLUMN_KEY = 'created_date';
    const POST_SLUG = 'pwa_users';

    public function __construct()
    {
        add_action('init', [$this, 'register']);
        add_action('manage_posts_custom_column', [$this, 'addCustomColumn'], 10, 2);
        add_filter('manage_edit-' . self::POST_SLUG . '_columns', [$this, 'manageColumns']);
        add_filter("manage_edit-" . self::POST_SLUG . "_sortable_columns", [$this, 'sortableColumns']);
    }

    public function register()
    {
        $admin = get_role('administrator');
        $admin->add_cap(self::MANAGE_CAP);

        register_post_type(self::POST_SLUG, [
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
}