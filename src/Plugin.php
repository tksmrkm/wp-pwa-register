<?php

namespace WpPwaRegister;

class Plugin
{
    public function __construct($file)
    {
        register_activation_hook($file, [$this, 'activate']);
        register_deactivation_hook($file, [$this, 'deactivate']);
    }

    public function activate()
    {
        add_rewrite_rule('^register.js$', 'index.php?register=1');
        add_rewrite_rule('^service-worker.js$', 'index.php?service-worker=1');
        add_rewrite_rule('^manifest.json$', 'index.php?manifest=1');
        flush_rewrite_rules();
    }

    public function deactivate()
    {
        flush_rewrite_rules();
    }
}