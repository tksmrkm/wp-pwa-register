<?php

namespace WpPwaRegister\Notifications;

use WpPwaRegister\Customizer;
use WpPwaRegister\Firebase;
use WpPwaRegister\Logs;

class Subscribe
{
    const FCM_SERVER = 'https://iid.googleapis.com/iid/v1:batchAdd';
    const FCM_BATCH_MAX_COUNT = 1000;
    private $firebase_server_key;
    private Logs $logs;

    public function __construct(Customizer $customizer, Logs $logs)
    {
        $this->firebase_server_key = $customizer->get_theme_mod(Firebase::CUSTOMIZER_KEY_SERVER_KEY);
        $this->logs = $logs;
    }

    public function subscribe($tokens)
    {
        $this->logs->debug($tokens);

        $headers = [
            'Authorization: key=' . $this->firebase_server_key,
            'Content-Type: application/json'
        ];

        $data = [
            'to' => '/topics/' . NotificationHttpV1::TOPIC_ALL,
            'registration_tokens' => $tokens,
            'priority' => 'high'
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
            ]
        ];

        $this->logs->debug($retval);
    }
}