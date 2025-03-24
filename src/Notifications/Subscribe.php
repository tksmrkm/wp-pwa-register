<?php

namespace WpPwaRegister\Notifications;

use GuzzleHttp\ClientInterface;
use WpPwaRegister\Customizer;
use WpPwaRegister\Firebase;
use WpPwaRegister\GoogleClient;
use WpPwaRegister\Logs;

class Subscribe
{
    const FCM_SERVER = 'https://iid.googleapis.com/iid/v1:batchAdd';
    const FCM_BATCH_MAX_COUNT = 1000;

    private $firebase_server_key;
    private Logs $logs;
    private ClientInterface $client;

    public function __construct(Customizer $customizer, Logs $logs, GoogleClient $client)
    {
        $this->firebase_server_key = $customizer->get_theme_mod(Firebase::CUSTOMIZER_KEY_SERVER_KEY);
        $this->logs = $logs;
        $this->client = $client->getClient();
    }

    public function subscribe($tokens)
    {
        $headers = [
            'Authorization: key=' . $this->firebase_server_key,
            'Content-Type: application/json'
        ];

        $data = [
            'to' => '/topics/' . NotificationHttpV1::TOPIC_ALL,
            'registration_tokens' => $tokens,
            'priority' => 'high'
        ];

        $result = $this->client->request('POST', self::FCM_SERVER, [
            'json' => $data
        ]);

        return json_decode($result->getBody()->getContents());
    }
}