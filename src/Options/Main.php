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
        global $wpdb;
$query = <<<QUERY
SELECT
    Post.post_title as id,
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
LIMIT 1
;
QUERY
;
        $result = $wpdb->get_results($query);
        $json = json_encode($result);
        include_once ROOT . DS . 'templates' . DS . 'options' . DS . 'main.php';
    }
}