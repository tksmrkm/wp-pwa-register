<?php

namespace WpPwaRegister\Notifications;

use Google\Client;
use WP_Post;
use WpPwaRegister\Analyzer\Analyzer;
use WpPwaRegister\Analyzer\Database;
use WpPwaRegister\Logs;
use WpPwaRegister\Customizer;
use WpPwaRegister\Firebase;

class NotificationHttpV1
{
    const POST_SLUG = 'pwa_http_v1';
    const META_HEADLINE = 'headline';
    const META_ICON = 'icon';
    const META_LINK = 'link';
    const TOPIC_ALL = 'all';
    const CUSTOMIZER_CONFIG_PATH_KEY = 'certs-path';
    const CUSTOMIZER_CONFIG_REWRITE_LINK_KEY = 'rewrite-link-flag';
    const FIREBASE_MESSAGING_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    const DELETION_COLUMN_KEY = 'deletion';
    const COUNT_COLUMN_KEY = 'count';
    const FILTER_HOOK_LINK = 'pwa_push_link';

    private Database $db;
    private Logs $logs;
    private Customizer $customizer;

    public function __construct(Database $db, Logs $logs, Customizer $customizer)
    {
        $this->db = $db;
        $this->logs = $logs;
        $this->customizer = $customizer;

        add_action('init', [$this, 'register']);
        add_action('publish_' . self::POST_SLUG, [$this, 'publish'], 10, 2);
        add_action('rest_api_init', [$this, 'restApiInit']);
        add_action('trash_' . self::POST_SLUG, [$this, 'trashPost'], 10, 2);
        add_action('manage_posts_custom_column', [$this, 'addCustomColumn'], 10, 2);
        add_filter('manage_edit-' . self::POST_SLUG . '_columns', [$this, 'manageColumns']);
        add_filter(self::FILTER_HOOK_LINK, [$this, 'pushLink'], 10, 2);
    }

    public function pushLink($link, $post_id)
    {
        $flag = $this->customizer->get_theme_mod(self::CUSTOMIZER_CONFIG_REWRITE_LINK_KEY, false);

        if ($flag) {
            $base = get_home_url() . '/' . Analyzer::RESOURCE_PATH;
            return $base . '?link=' . rawurlencode($link) . '&pid=' . $post_id;
        }

        return $link;
    }

    public function manageColumns($columns)
    {
        $columns[self::DELETION_COLUMN_KEY] = '削除';
        $columns[self::COUNT_COLUMN_KEY] = 'カウント';
        return $columns;
    }

    public function addCustomColumn($column, $post_id)
    {
        if ($column === self::DELETION_COLUMN_KEY) {
            echo '<a href="',
                menu_page_url(Option::MENU_KEY, false),
                '&post_id=',
                $post_id,
                '" onClick="return confirm(\'削除を実行する？\')">delete: ',
                $post_id,
                '</a>';
        }

        if ($column === self::COUNT_COLUMN_KEY) {
            echo $this->db->get_count($post_id);
        }
    }

    public function trashPost($post_id, $post)
    {
        if ($post->post_type === self::POST_SLUG) {
            wp_trash_post($post_id);
        }
    }

    public function updateCallback($value, $post)
    {
        if (!$value) {
            return;
        }

        foreach ($value as $key => $data) {
            if (is_array($data)) {
                foreach ($data as $record) {
                    add_post_meta($post->ID, $key, $record);
                }
            } else {
                add_post_meta($post->ID, $key, $data);
            }
        }
    }

    public function restApiInit()
    {
        register_rest_field(self::POST_SLUG, 'post_meta', [
            'update_callback' => [$this, 'updateCallback']
        ]);
    }

    public function publish($post_id, WP_Post $post)
    {
        $title = $post->post_title;
        $headline = get_post_meta($post_id, self::META_HEADLINE, true);
        $icon = get_post_meta($post_id, self::META_ICON, true);
        $link = get_post_meta($post_id, self::META_LINK, true);
        $link = apply_filters(self::FILTER_HOOK_LINK, $link, $post_id);

        $google = new Client();
        $authConfigPath = $this->customizer->get_theme_mod(self::CUSTOMIZER_CONFIG_PATH_KEY);
        $google->setAuthConfig($authConfigPath);
        $google->useApplicationDefaultCredentials();
        $google->addScope(self::FIREBASE_MESSAGING_SCOPE);
        $client = $google->authorize();

        $data = [
            'message' => [
                'topic' => self::TOPIC_ALL,
                'notification' => [
                    'title' => $headline,
                    'body' => $title
                ],
                'data' => [
                    'version' => "v2",
                    'icon' => $icon,
                    'link' => $link,
                    'post_id' => "$post_id"
                ],
                'webpush' => [
                    'fcm_options' => [
                        'analytics_label' => "$post_id"
                    ]
                ]
            ]
        ];

        $project_id = $this->customizer->get_theme_mod(Firebase::CUSTOMIZER_KEY_PROJECT_ID);
        $target = 'https://fcm.googleapis.com/v1/projects/' . $project_id . '/messages:send';

        $result = $client->request('POST', $target, [
            'json' => $data
        ]);

        $json = json_decode($result->getBody()->getContents());

        $this->logs->debug($data);
        $this->logs->debug($json);
    }

    public function register()
    {
        $res = register_post_type(self::POST_SLUG, [
            'label' => 'PUSH通知HTTPv1',
            'public' => false,
            'show_in_rest' => true,
            'show_ui' => true,
            'supports' => [
                'title',
                'custom-fields'
            ]
        ]);
    }
}