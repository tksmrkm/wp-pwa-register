<?php

namespace WpPwaRegister\Options;

use WP_Query;

use const WpPwaRegister\ROOT;
use const WpPwaRegister\DS;

class Main
{
    const SLUG = 'wp-pwa-register';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'adminMenu']);
    }

    public function adminMenu()
    {
        add_menu_page('WP PWA REGISTER', 'WP PWA REGISTER', 'administrator', self::SLUG, [$this, 'adminMenuView']);
    }

    public function adminMenuView()
    {
        $pwa_users = new WP_Query([
            'post_type' => 'pwa_users',
            'post_status' => 'any',
            'posts_per_page' => -1
        ]);
        $mapped = array_map(function($user) {
            return [
                'key' => $user->post_title,
                'token' => get_post_meta($user->ID, 'token', true)
            ];
        }, $pwa_users->posts);
        $filtered = array_filter($mapped, function($user) {
            return $user['key'] && $user['token'];
        });
        include_once ROOT . DS . 'templates' . DS . 'options' . DS . 'main.php';
    }
}