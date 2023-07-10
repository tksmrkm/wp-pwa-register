<?php

namespace WpPwaRegister;

class Posts
{
    use traits\Singleton;

    public function init()
    {
        add_action('init', [$this, 'register']);
    }

    public function register()
    {
        $this->registerPwaUsers();
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
}
