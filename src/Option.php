<?php

namespace WpPwaRegister;

class Option
{
    const MENU_KEY = 'wp-pwa-register-option';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'adminMenu']);
    }

    public function adminMenu()
    {
        add_menu_page('WP PWA Register', 'WP PWA Register', 'administrator', self::MENU_KEY, '__return_false');
    }
}
