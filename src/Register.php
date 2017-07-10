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
        $register = get_query_var('register');
        var_dump($register);
        if ($register) {
            error_log($register);
            var_dump($register);
            exit;
        }
    }
}