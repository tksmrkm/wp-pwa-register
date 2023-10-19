<?php

namespace WpPwaRegister;

class Manifest
{
    const QUERY_ROUTE_KEY = 'pwa-manifest';

    private Customizer $customizer;

    public function __construct(Customizer $customizer)
    {
        $this->customizer = $customizer;

        add_filter('query_vars', [$this, 'addVar']);
        add_action('init', [$this, 'registerRoute']);
        add_action('parse_request', [$this, 'parseRequest']);
    }

    public function registerRoute()
    {
        add_rewrite_rule('^pwa-manifest.json/?$', 'index.php?' . self::QUERY_ROUTE_KEY . '=1', 'top');
    }

    public function addVar($vars)
    {
        $vars[] = self::QUERY_ROUTE_KEY;
        return $vars;
    }

    public function parseRequest($wp)
    {
        if (isset($wp->query_vars[self::QUERY_ROUTE_KEY])) {
            if ($wp->query_vars[self::QUERY_ROUTE_KEY] === '1') {
                header('Content-Type: application/json; charset=UTF-8');
                $name = $this->customizer->get_theme_mod('name', get_bloginfo('name'));
                $shortName = $this->customizer->get_theme_mod('short_name') ?: null;
                $display = $this->customizer->get_theme_mod('display', 'standalone');
                $startUrl = $this->customizer->get_theme_mod('start-url', '/');
                $themeColor = $this->customizer->get_theme_mod('theme-color', '#333');
                $backgroundColor = $this->customizer->get_theme_mod('background-color', '#FFF');
                $icon = $this->getIcon();
                include_once ROOT . DS . 'templates' . DS . 'manifest.json';
                exit;
            }
        }
    }

    private function getIcon()
    {
        $src = $this->customizer->get_theme_mod('icon-src');
        $sizes = $this->customizer->get_theme_mod('icon-sizes');
        $type = $this->customizer->get_theme_mod('icon-type');

        if ($src && $sizes && $type) {
            return json_encode([
                'src' => $this->customizer->get_theme_mod('icon-src'),
                'sizes' => $this->customizer->get_theme_mod('icon-sizes'),
                'type' => $this->customizer->get_theme_mod('icon-type', 'image/png')
            ]);
        }

        return false;
    }
}
