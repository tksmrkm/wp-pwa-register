<?php

namespace WpPwaRegister;

class ServiceWorker
{
    use Singleton;

    private function init()
    {
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
            exit;
        }
    }
}