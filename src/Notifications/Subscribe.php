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
            ];

            $accessToken = $client->fetchAccessTokenWithAssertion();
            $accessTokenString = $accessToken['access_token'];

            $headers = [
                "Authorization: Bearer {$accessTokenString}",
                'Content-Type: application/json',
                'access_token_auth: true'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_URL, self::FCM_SERVER);
            $response = curl_exec($ch);

            $error = curl_error($ch);
            $errno = curl_errno($ch);

            curl_close($ch);

            $retval = [
                'result' => json_decode($response),
                'curl' => [
                    'error' => $error,
                    'errno' => $errno
                ],
            ];

            return $retval;
        } else {
            $this->logs->debug("Client is not prepared yet");
        }
    }
}