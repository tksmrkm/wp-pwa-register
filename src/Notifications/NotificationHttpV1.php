<?php

namespace WpPwaRegister\Notifications;

use Google\Client;
use WP_Post;
use WpPwaRegister\Logs;
use WpPwaRegister\Customizer;
use WpPwaRegister\Firebase;

class NotificationHttpV1
{
    const POST_SLUG = 'pwa_http_v1';
    const META_CONTENT = 'content';
    const META_ICON = 'icon';
    const META_LINK = 'link';
    const TOPIC_ALL = 'all';
    const CUSTOMIZER_CONFIG_PATH_KEY = 'certs-path';
    const FIREBASE_MESSAGING_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private Logs $logs;
    private Customizer $customizer;

    public function __construct(Logs $logs, Customizer $customizer)
    {
        $this->logs = $logs;
        $this->customizer = $customizer;

        add_action('init', [$this, 'register']);
        add_action('publish_' . self::POST_SLUG, [$this, 'publish'], 10, 2);
    }

    public function publish($post_id, WP_Post $post)
    {
        $post;
        $title = $post->post_title;
        $content = get_post_meta($post_id, self::META_CONTENT, true);
        $icon = get_post_meta($post_id, self::META_ICON, true);
        $link = get_post_meta($post_id, self::META_LINK, true);

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
                    'title' => $title,
                    'body' => $content
                ],
                'data' => [
                    'version' => 2,
                    'icon' => $icon,
                    'link' => $link,
                    'post_id' => $post_id
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
            'show_in_rest' => false,
            'show_ui' => true,
            'supports' => [
                'title',
                'custom-fields'
            ]
        ]);
    }
}