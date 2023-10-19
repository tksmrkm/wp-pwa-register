<?php

namespace WpPwaRegister;

class ServiceWorker
{
    const QUERY_ROUTE_KEY = 'pwa-service-worker';

    private Customizer $customizer;

    public function __construct(Customizer $customizer)
    {
        $this->customizer = $customizer;
        add_filter('query_vars', [$this, 'addVar']);
        add_action('init', [$this, 'registerRoute']);
        add_action('parse_request', [$this, 'parseRequest']);
    }

    public function registerRoute()
    {
        add_rewrite_rule('^pwa-service-worker.js$', 'index.php?' . self::QUERY_ROUTE_KEY . '=1', 'top');
    }

    public function addVar($vars)
    {
        $vars[] = self::QUERY_ROUTE_KEY;
        return $vars;
    }

    public function parseRequest($wp)
    {
        if (isset($wp->query_vars[self::QUERY_ROUTE_KEY])) {
            if ($wp->query_vars[self::QUERY_ROUTE_KEY] === '1') {
                header('Content-Type: application/javascript; charset=UTF-8');
                $title = $this->customizer->get_theme_mod('name');
                $icon = $this->customizer->get_theme_mod('icon-src');
                require_once ROOT . DS . 'templates' . DS . 'service-worker.js';
                exit;
            }
        }
    }
}