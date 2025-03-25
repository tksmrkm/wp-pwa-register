<?php

namespace WpPwaRegister;

use Google\Client;
use GuzzleHttp\ClientInterface as GuzzleClient;

class GoogleClient
{
    const CUSTOMIZER_CONFIG_PATH_KEY = 'certs-path';
    const FIREBASE_MESSAGING_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private Client $client;
    private string $errorMessage;

    public function __construct(Customizer $customizer)
    {
        $google = new Client();
        $authConfigPath = $customizer->get_theme_mod(self::CUSTOMIZER_CONFIG_PATH_KEY);

        if ($authConfigPath) {
            if (file_exists($authConfigPath)) {
                $google->setAuthConfig($authConfigPath);
                $google->useApplicationDefaultCredentials();
                $google->addScope(self::FIREBASE_MESSAGING_SCOPE);
                $this->client = $google;
            } else {
                $this->errorMessage = 'カスタマイズ > WP PWA Register > Firebase > Certs JSON Path: 認証ファイルのパスが間違っています。 [ ' . $authConfigPath . ' ]';
                add_action('admin_notices', [$this, 'notice']);
            }
        } else {
            $this->errorMessage = 'カスタマイズ > WP PWA Register > Firebase > Certs JSON Pathが設定されていません。';
            add_action('admin_notices', [$this, 'notice']);
        }
    }

    public function getClient()
    {
        return $this->client;
    }

    public function notice()
    {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p>' . $this->errorMessage . '</p>';
        echo '</div>';
    }
}