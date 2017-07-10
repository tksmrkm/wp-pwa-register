<?php

namespace WpPwaRegister;

class Manifest
{
    use Singleton;

    private function init()
    {
        add_filter('query_vars', [$this, 'addVar']);
        add_action('template_redirect', [$this, 'redirect']);
    }

    public function addVar($vars)
    {
        $vars[] = 'manifest';
        return $vars;
    }

    public function redirect()
    {
        if ($manifest = get_query_var('manifest')) {
            header('Content-Type: application/json; charset=UTF-8');
            require_once ROOT . DS . 'templates' . DS . 'manifest.json';
            exit;
        }
    }
}
