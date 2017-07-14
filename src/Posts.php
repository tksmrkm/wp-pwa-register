<?php

namespace WpPwaRegister;

class Posts
{
    use Singleton;

    public function init()
    {
        add_action('init', [$this, 'register']);
        add_action('manage_posts_custom_column', [$this, 'addCustomColumn'], 10, 2);

        add_filter('manage_edit-firebase_users_columns', [$this, 'firebaseUsersManageColumns']);
    }

    public function register()
    {
        register_post_type('firebase_users', [
            'label' => 'firebase_users',
            'labels' => [
                'name' => 'firebase_users',
                'singular_name' => 'firebase_users'
            ],
            'public' => true,
            'show_in_rest' => true,
            'show_ui' => true,
            'supports' => [
                'title',
                'editor',
                'custom-fields'
            ]
        ]);
    }

    public function firebaseUsersManageColumns($columns)
    {
        $columns['_did_not_reach'] = '非到達数';
        return $columns;
    }

    public function addCustomColumn($column, $post_id)
    {
        if ($column === '_did_not_reach') {
            echo get_post_meta($post_id, '_did_not_reach', true);
        }
    }
}