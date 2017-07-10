<?php

namespace WpPwaRegister;

class Register
{
    use Singleton;

    public function init()
    {
        add_action('template_redirect', [$this, 'redirect']);
    }

    public function redirect()
    {
        $register = get_query_var('register');
    }
}