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
        $vars['manifest'] = 1;
        // var_dump($vars);
        return $vars;
    }

    public function redirect()
    {
        if ($manifest = get_query_var('manifest')) {
            // var_dump($manifest, get_query_var('m'));
            exit;
        }
    }
}