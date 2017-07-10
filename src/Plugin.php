<?php

namespace WpPwaRegister;

class Plugin
{
    public function __construct($file)
    {
        register_deactivation_hook($file, 'flush_rewrite_rules');
        register_activation_hook($file, [$this, 'activate']);
        add_action('init', [$this, 'rewrite_rules']);
        add_filter('redirect_canonical', [$this, 'canonical'], 10, 2);
    }

    public function activate()
    {
        $this->rewrite_rules();
        flush_rewrite_rules();
    }

    public function rewrite_rules()
    {
        add_rewrite_rule('^pwa-register.js/?$', 'index.php?register=1', 'top');
        add_rewrite_rule('^pwa-service-worker.js/?$', 'index.php?service-worker=1', 'top');
        add_rewrite_rule('^pwa-manifest.json/?$', 'index.php?manifest=1', 'top');
    }

    public function canonical($redirect, $request)
    {
        $untrail = untrailingslashit($request);
        $exploded = explode('/', $untrail);
        $filename = array_pop($exploded);

        if (preg_match('/^pwa-(?:register|service-worker|manifest)\.js(?:on)?$/', $filename)) {
            return $request;
        }

        return $redirect;
    }
}