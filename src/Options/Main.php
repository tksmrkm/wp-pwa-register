<?php

namespace WpPwaRegister\Options;

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
        $ids = $this->getRegistrtionIds();
        include_once ROOT . DS . 'templates' . DS .'options' . DS . 'main.php';
    }

    public function getRegistrtionIds()
    {
        global $wpdb;
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
;
QUERY
;
        $pwa_users = $wpdb->get_results($query);
        $chunks = array_chunk($pwa_users, 1000);
        return array_map(function($chunk) {
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
    }
}