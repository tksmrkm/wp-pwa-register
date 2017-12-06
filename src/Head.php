<?php

namespace WpPwaRegister;

class Head
{
    use traits\Singleton;
    
    public function init($c)
    {
        $this->customizer = $c['customizer'];
        add_action('wp_head', [$this, 'wpHead']);
    }

    public function wpHead()
    {
        $themeColor = $this->customizer->get_theme_mod('theme-color', '#333');
        echo '<meta name="theme-color" content="', $themeColor, '">';
    }
}