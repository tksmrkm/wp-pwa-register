<?php

namespace WpPwaRegister;

class Register
{
    use traits\Singleton;
    
    public function init()
    {
        add_filter('query_vars', [$this, 'addVars']);
        add_action('template_redirect', [$this, 'redirect']);
        add_action('wp_enqueue_scripts', [$this, 'scripts']);
    }

    public function scripts()
    {
        $basename = basename(dirname(__DIR__));
        $style_path = '/styles/register.css';
        $file_url = plugins_url($basename . $style_path);
        wp_enqueue_style('wp-pwa-register-register', $file_url, [], VERSION);
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