<?php

namespace WpPwaRegister\Analyzer;

use WpPwaRegister\Notifications\Option as NotificationsOption;

use const WpPwaRegister\DS;
use const WpPwaRegister\ROOT;

class Option
{
    const MIGRATE_DB_OPTION = 'analyzer-db-migration';

    private Database $db;

    public function __construct($db)
    {
        $this->db = $db;
        add_action('admin_menu', [$this, 'adminMenu']);
    }

    public function adminMenu()
    {
        add_submenu_page(NotificationsOption::MENU_KEY, 'AnalyzerDB', 'AnalyzerDB', 'administrator', self::MIGRATE_DB_OPTION, [$this, '_view']);
    }

    public function _view()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $action_url = menu_page_url(self::MIGRATE_DB_OPTION, false);
            include_once ROOT . DS . 'templates' . DS . 'analyzer' . DS . 'migrate.php';
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($_POST['action'] === 'migrate') {
                $this->db->register();
                echo 'Migrated DB';
            } else if ($_POST['action'] === 'delete') {
                $this->db->deregister();
                echo 'Deleted DB';
            }
        }
    }
}