<?php

namespace WpPwaRegister;

class ServiceWorker
{
    use traits\Singleton;
    
    private function init($container)
    {
        $this->customizer = $container['customizer'];
        add_filter('query_vars', [$this, 'addVar']);
        add_action('template_redirect', [$this, 'redirect']);
    }

    public function addVar($vars)
    {
        $vars[] = 'service-worker';
        return $vars;
    }

    public function redirect()
    {
        if ($serviceWorker = get_query_var('service-worker')) {
            header('Content-Type: application/javascript; charset=UTF-8');
            $title = $this->customizer->get_theme_mod('name');
            $icon = $this->customizer->get_theme_mod('icon-src');
            require_once ROOT . DS . 'templates' . DS . 'service-worker.js';
            exit;
        }
    }
}