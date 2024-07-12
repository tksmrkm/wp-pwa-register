<?php

namespace WpPwaRegister;

class Firebase
{
    use traits\Singleton;

    const CUSTOMIZER_KEY_APP_ID = 'app-id';
    const CUSTOMIZER_KEY_API_KEY = 'api-key';
    const CUSTOMIZER_KEY_PROJECT_ID = 'project-id';
    const CUSTOMIZER_KEY_SENDER_ID = 'sender-id';
    const CUSTOMIZER_KEY_SERVER_KEY = 'server-key';

    private Customizer $customizer;

    public function init(Customizer $customizer)
    {
        $this->customizer = $customizer;

        add_filter('script_loader_tag', [$this, 'scriptLoader'], 10, 3);
    }

    public function scriptLoader($tag, $handle, $src)
    {
        if ($handle === 'pwa-firebase') {
            $apiKey = $this->customizer->get_theme_mod('api-key');
            $projectId = $this->customizer->get_theme_mod('project-id');
            $senderId = $this->customizer->get_theme_mod('sender-id');

            ob_start();
            include_once ROOT . DS . 'templates' . DS . 'firebase.php';
            $content = ob_get_clean();

            return $tag . PHP_EOL . $content;
        }

        return $tag;
    }
}