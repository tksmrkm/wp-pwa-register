<?php

namespace WpPwaRegister\Notifications;

use WP_Post;
use WpPwaRegister\Logs;
use WpPwaRegister\Customizer;
use WpPwaRegister\Firebase;
use WpPwaRegister\GoogleClient;

use const WpPwaRegister\ROOT;
use const WpPwaRegister\DS;

class NotificationHttpV1
{
    const POST_SLUG = 'pwa_http_v1';
    const META_HEADLINE = 'headline';
    const META_ICON = 'icon';
    const META_LINK = 'link';
    const TOPIC_ALL = 'all';
    const DELETION_COLUMN_KEY = 'deletion';
    const META_BOX_KEY = 'meta_box';

    private Logs $logs;
    private Customizer $customizer;
    private GoogleClient $client;

    public function __construct(Logs $logs, Customizer $customizer, GoogleClient $client)
    {
        $this->logs = $logs;
        $this->customizer = $customizer;
        $this->client = $client;

        add_action('init', [$this, 'register']);
        add_action('publish_' . self::POST_SLUG, [$this, 'publish'], 10, 2);
        add_action('rest_api_init', [$this, 'restApiInit']);
        add_action('admin_init', [$this, 'adminInit']);
        add_action('save_post_' . self::POST_SLUG, [$this, 'savePost']);
    }

    public function savePost($post_id)
    {
        if (
            isset($_POST["http_v1_" . self::META_HEADLINE])
            && isset($_POST["http_v1_" . self::META_ICON])
            && isset($_POST["http_v1_" . self::META_LINK])
        ) {
            update_post_meta($post_id, self::META_HEADLINE, $_POST["http_v1_" . self::META_HEADLINE]);
            update_post_meta($post_id, self::META_ICON, $_POST["http_v1_" . self::META_ICON]);
            update_post_meta($post_id, self::META_LINK, $_POST["http_v1_" . self::META_LINK]);
        }
    }

    public function adminInit()
    {
        add_meta_box(self::class . self::META_BOX_KEY, "Set meta", [$this, 'addMetaBox'], self::POST_SLUG);
    }

    public function addMetaBox()
    {
        $id = $_GET["post"] ?? null;
        $default_headline = $this->customizer->get_theme_mod('notifications_' . self::META_HEADLINE);
        $default_icon = $this->customizer->get_theme_mod('notifications_' . self::META_ICON);
        $default_link = $this->customizer->get_theme_mod('notifications_' . self::META_LINK);
        $headline = $id ? get_post_meta($id, self::META_HEADLINE, true): $default_headline;
        $icon = $id ? get_post_meta($id, self::META_ICON, true): $default_icon;
        $link = $id ? get_post_meta($id, self::META_LINK, true): $default_link;

        include_once ROOT . DS . 'templates' . DS . 'meta_boxes' . DS . 'create_http_v1.php';
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
        $headline = $_POST["http_v1_" . self::META_HEADLINE] ?? get_post_meta($post_id, self::META_HEADLINE, true);
        $icon = $_POST["http_v1_" . self::META_ICON] ?? get_post_meta($post_id, self::META_ICON, true);
        $link = $_POST["http_v1_" . self::META_LINK] ?? get_post_meta($post_id, self::META_LINK, true);

        if (!$headline || !$icon || !$link) {
            $this->logs->debug([
                "msg" => "skip send notification, required params are not set",
                [$headline, $icon, $link]
            ]);

            return false;
        }

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

        $client = $this->client->getClient();

        if ($client) {
            $result = $client->authorize()->request('POST', $target, [
                'json' => $data
            ]);

            $json = json_decode($result->getBody()->getContents());

            $this->logs->debug($data);
            $this->logs->debug($json);
        }
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
                'custom-fields',
                'editor'
            ]
        ]);

        $default_headline = $this->customizer->get_theme_mod('notifications_' . self::META_HEADLINE);
        $default_icon = $this->customizer->get_theme_mod('notifications_' . self::META_ICON);
        $default_link = $this->customizer->get_theme_mod('notifications_' . self::META_LINK);

        register_post_meta(self::POST_SLUG, self::META_HEADLINE, [
            "type" => "string",
            "label" => "Headline",
            "single" => true,
            "show_in_rest" => true,
            "default" => $default_headline
        ]);

        register_post_meta(self::POST_SLUG, self::META_ICON, [
            "type" => "string",
            "label" => "Icon",
            "single" => true,
            "show_in_rest" => true,
            "default" => $default_icon
        ]);

        register_post_meta(self::POST_SLUG, self::META_LINK, [
            "type" => "string",
            "label" => "Link",
            "single" => true,
            "show_in_rest" => true,
            "default" => $default_link
        ]);
    }
}