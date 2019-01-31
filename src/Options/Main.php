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
        $start = microtime(true);
        $legacy = $this->legacyMessage();
        $mid = microtime(true);
        $modern = $this->newMessage();
        $end = \microtime(true);

        var_dump($mid - $start, $end - $mid);
        var_dump($legacy, $modern);
    }

    public function legacyMessage()
    {
        $pwa_users = new WP_Query([
            'post_type' => 'pwa_users',
            'post_status' => 'any',
            'posts_per_page' => 1000,
            'orderby' => "ID"
        ]);

        $chunks = array_chunk($pwa_users->posts, 1000);
        return array_map(function($chunk) {
            $ids = [
                "endpoints" => [],
                "ids" => []
            ];

            foreach ($chunk as $user) {
                $token = get_post_meta($user->ID, 'token', true);

                if ($token) {
                    $ids["endpoints"][] = $token;
                    $ids["ids"][] = $user->ID;
                }
            }

            return $ids;
        }, $chunks);
    }

    public function newMessage()
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
LIMIT 1000
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