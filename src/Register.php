<?php

namespace WpPwaRegister;

class Register
{
    use Singleton;

    public function init()
    {
        add_filter('query_vars', [$this, 'addVars']);
        add_action('template_redirect', [$this, 'redirect']);
    }

    public function addVars($vars)
    {
        $vars[] = 'register';
        return $vars;
    }

    public function redirect()
    {
        if ($register = get_query_var('register')) {
            $var = 'sent from php value';
            header('Content-Type: application/javascript; charset=UTF-8');
            require_once ROOT . DS . 'templates' . DS . 'register.js';
            exit;
        }
    }
}