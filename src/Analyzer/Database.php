<?php

namespace WpPwaRegister\Analyzer;

use QM_DB;
use WpPwaRegister\Notifications\NotificationHttpV1;

class Database
{
    const VERSION = '0.0.1';
    const INSTALLED_VERSION_KEY = 'pwa_analyzer_db_version';

    const DB_TABLE_SLUG = 'pwa_analyzer';

    private QM_DB $db;
    private string $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;

        $this->table_name = $wpdb->prefix . self::DB_TABLE_SLUG;

        register_activation_hook(__FILE__, [$this, 'register']);
        add_action('delete_' . NotificationHttpV1::POST_SLUG, [$this, 'delete']);
    }

    public function delete($post_id)
    {
        $query = <<<QUERY
        DELETE FROM {$this->table_name}
        WHERE pid = %d;
        QUERY;
        $prepare = $this->db->prepare($query, $post_id);
        $this->db->query($prepare);
    }

    public function register()
    {
        $installed_version = get_option(self::INSTALLED_VERSION_KEY);

        if ($installed_version !== self::VERSION) {
            $sql = <<<QUERY
            CREATE TABLE {$this->table_name} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                pid BIGINT UNSIGNED NOT NULL,
                uid VARCHAR(32),
                UNIQUE KEY id (id)
            )
            CHARACTER SET 'utf8';
            QUERY;

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            dbDelta($sql);

            update_option(self::INSTALLED_VERSION_KEY, self::VERSION);
        }
    }

    public function deregister()
    {
        $this->db->query("DROP TABLE IF EXISTS {$this->table_name};");
        delete_option(self::INSTALLED_VERSION_KEY);
    }

    public function insert_record($pid, $uid)
    {
        $query = [
            'pid' => $pid,
        ];

        $format = ["%d"];

        if ($uid) {
            $query['uid'] = $uid;
            $format[] = '%s';
        }

        $result = $this->db->insert($this->table_name, $query, $format);

        return $result;
    }

    public function get_count($pid)
    {
        $sql = <<<QUERY
        SELECT count(id) as count FROM {$this->table_name}
        WHERE pid = %d;
        QUERY;
        $query = $this->db->prepare($sql, $pid);
        // $query = "SELECT * FROM {$this->table_name};";
        $count = $this->db->get_var($query);

        return $count;
    }
}