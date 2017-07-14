<?php

namespace WpPwaRegister;

class Firebase
{
    use Singleton;

    public function init($container)
    {
        $this->customizer = $container['customizer'];

        add_action('wp_enqueue_scripts', [$this, 'enqueueScript']);
        add_filter('script_loader_tag', [$this, 'scriptLoader'], 10, 3);
    }

    public function enqueueScript()
    {
        $theme = wp_get_theme();

        wp_enqueue_script('pwa-firebase', 'https://www.gstatic.com/firebasejs/4.1.3/firebase.js', [], null, true);
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