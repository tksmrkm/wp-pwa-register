<?php

namespace WpPwaRegister\Analyzer;

class Analyzer
{
    const QUERY_ROUTE_KEY = 'pwa-analyzer';
    const RESOURCE_PATH = 'pwa_analyzer';

    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;

        add_filter('query_vars', [$this, 'addVars']);
        add_action('init', [$this, 'registerRoute']);
        add_action('parse_request', [$this, 'parseRequest']);
    }

    public function addVars($vars)
    {
        $vars[] = self::QUERY_ROUTE_KEY;
        return $vars;
    }

    public function registerRoute()
    {
        add_rewrite_rule('^' . self::RESOURCE_PATH . '$', 'index.php?' . self::QUERY_ROUTE_KEY . '=1', 'top');
    }

    public function parseRequest($wp)
    {
        if (isset($wp->query_vars[self::QUERY_ROUTE_KEY])) {
            if ($wp->query_vars[self::QUERY_ROUTE_KEY] === '1') {
                /**
                 * expected query params
                 * 
                 * - link: required, urlencoded URL.
                 * - pid: required, pwa_http_v1 post id.
                 * - uid: nullable, service worker detected id.
                 */
                if (!isset($_GET['link']) || !isset($_GET['pid'])) {
                    var_dump("\$_GET: ", $_GET);
                    exit;
                }

                $result = $this->db->insert_record($_GET['pid'], $_GET['uid'] ?? null);

                header('Location: ' . rawurldecode($_GET['link']));
                exit(302);
            }
        }
    }
}
