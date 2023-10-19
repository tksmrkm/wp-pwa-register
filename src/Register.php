<?php

namespace WpPwaRegister;

class Register
{
    const QUERY_ROUTE_KEY = 'pwa-register';

    private $valid; // callable

    public function __construct(callable $valid)
    {
        $this->valid = $valid;

        add_filter('query_vars', [$this, 'addVars']);
        add_action('init', [$this, 'registerRoute']);
        add_action('parse_request', [$this, 'parseRequest']);
        add_action('wp_enqueue_scripts', [$this, 'scripts']);
    }
    
    public function scripts()
    {
        $basename = basename(dirname(__DIR__));
        $style_path = '/styles/register.css';
        $file_url = plugins_url($basename . $style_path);
        wp_enqueue_style('wp-pwa-register-register', $file_url, [], VERSION);

        if (call_user_func($this->valid)) {
            wp_enqueue_script('pwa-register', home_url('/pwa-register.js'), [], VERSION, true);
        }
    }

    public function registerRoute()
    {
        add_rewrite_rule('^pwa-register.js$', 'index.php?' . self::QUERY_ROUTE_KEY . '=1', 'top');
    }

    public function addVars($vars)
    {
        $vars[] = self::QUERY_ROUTE_KEY;
        return $vars;
    }

    public function parseRequest($wp)
    {
        if (isset($wp->query_vars[self::QUERY_ROUTE_KEY])) {
            if ($wp->query_vars[self::QUERY_ROUTE_KEY] === '1') {
                header('Content-Type: application/javascript; charset=UTF-8');
                require_once ROOT . DS . 'templates' . DS . 'register.js';
                exit;
            }
        }
    }
}