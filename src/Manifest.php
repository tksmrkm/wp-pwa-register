<?php

namespace WpPwaRegister;

class Manifest
{
    use Singleton;

    private function init($container)
    {
        $this->customizer = $container['customizer'];
        add_filter('query_vars', [$this, 'addVar']);
        add_action('template_redirect', [$this, 'redirect']);
    }

    public function addVar($vars)
    {
        $vars[] = 'manifest';
        return $vars;
    }

    public function redirect()
    {
        if ($manifest = get_query_var('manifest')) {
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
