<?php

namespace WpPwaRegister\Options;

use WP_Query;

use const WpPwaRegister\ROOT;
use const WpPwaRegister\DS;

class Unsubscribe
{
    const SLUG = 'unsubscribe';
    const SETTING = 'wp-pwa-unsubscribe';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'adminMenu']);
    }

    public function adminMenu()
    {
        add_submenu_page(Main::SLUG, '未購読削除', '未購読削除', 'administrator', self::SLUG, [$this, 'adminMenuView']);
    }

    public function adminMenuView()
    {
        include_once ROOT . DS . 'templates' . DS . 'options' . DS . 'unsubscribe.php';
    }
}