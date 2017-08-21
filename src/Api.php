<?php

namespace WpPwaRegister;

class Api
{
    use Singleton;

    const ACCESS_POINT = 'api_log';

    public function init($container)
    {
        $this->logs = $container['logs'];
        add_action('init', [$this, 'register']);
        add_filter('query_vars', [$this, 'addVars']);
        add_action('template_redirect', [$this, 'redirect']);
    }

    public function addVars($vars)
    {
        $vars[] = self::ACCESS_POINT;
        return $vars;
    }

    public function redirect()
    {
        if (get_query_var(self::ACCESS_POINT)) {
            $this->log();
            exit;
        }
    }

    public function register()
    {
        add_rewrite_rule('^api/log$', 'index.php?' . self::ACCESS_POINT . '=1', 'top');
    }

    private function log()
    {
        $this->logs->debug([
            'Server' => $_SERVER,
            'Post' => $_POST,
            'Get' => $_GET
        ]);
    }
}