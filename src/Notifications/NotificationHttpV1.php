<?php

namespace WpPwaRegister\Notifications;

use GuzzleHttp\ClientInterface;
use WP_Post;
use WpPwaRegister\Logs;
use WpPwaRegister\Customizer;
use WpPwaRegister\Firebase;
use WpPwaRegister\GoogleClient;

class NotificationHttpV1
{
    const POST_SLUG = 'pwa_http_v1';
    const META_HEADLINE = 'headline';
    const META_ICON = 'icon';
    const META_LINK = 'link';
    const TOPIC_ALL = 'all';
    const DELETION_COLUMN_KEY = 'deletion';

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
                'custom-fields'
            ]
        ]);
    }
}