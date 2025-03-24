<?php

namespace WpPwaRegister;

use Google\Client;
use GuzzleHttp\ClientInterface as GuzzleClient;
use WpPwaRegister\Notifications\NotificationHttpV1;

class GoogleClient
{
    private GuzzleClient $client;

    public function __construct(Customizer $customizer)
    {
        $google = new Client();
        $authConfigPath = $customizer->get_theme_mod(NotificationHttpV1::CUSTOMIZER_CONFIG_PATH_KEY);
        $google->setAuthConfig($authConfigPath);
        $google->useApplicationDefaultCredentials();
        $google->addScope(NotificationHttpV1::FIREBASE_MESSAGING_SCOPE);
        $this->client = $google->authorize();
    }

    public function getClient()
    {
        return $this->client;
    }
}