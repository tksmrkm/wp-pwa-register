<?php

namespace WpPwaRegister\Notifications;

use WpPwaRegister\Customizer;
use WpPwaRegister\Firebase;
use WpPwaRegister\GoogleClient;
use WpPwaRegister\Logs;

class Subscribe
{
    const FCM_SERVER = 'https://iid.googleapis.com/iid/v1:batchAdd';
    const FCM_BATCH_MAX_COUNT = 1000;

    private Logs $logs;
    private GoogleClient $client;

    public function __construct(GoogleClient $client, Logs $logs)
    {
        $this->logs = $logs;
        $this->client = $client;
    }

    public function subscribe($tokens)
    {
        $client = $this->client->getClient();

        if ($client) {
            $data = [
                'to' => '/topics/' . NotificationHttpV1::TOPIC_ALL,
                'registration_tokens' => $tokens,
                'priority' => 'high'
            ];

            $accessTokenArray = $client->fetchAccessTokenWithAssertion();
            $accessToken = $accessTokenArray["access_token"];
            $headers = [
                'access_token_auth' => true,
                'Authorization' => "Bearer {$accessToken}"
            ];

            $ch = curl_init(self::FCM_SERVER);
            curl_setopt($ch, CURLOPT_HEADER, $headers);

            $retval = [];

            $this->logs->debug($retval);

            return $retval;
        } else {
            $this->logs->debug("Client is not prepared yet");
        }
    }
}