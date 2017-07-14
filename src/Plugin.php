<?php

namespace WpPwaRegister;

class Plugin
{
    private function prepare()
    {
        $container = [];
        $container['customizer'] = Customizer::getInstance();
        Manifest::getInstance($container);
        Register::getInstance();
        ServiceWorker::getInstance();
        Firebase::getInstance($container);
        Posts::getInstance();

        $this->customizer = $container['customizer'];
    }

    public function __construct($file)
    {
        $this->prepare();

        register_deactivation_hook($file, 'flush_rewrite_rules');
        register_activation_hook($file, [$this, 'activate']);
        add_action('init', [$this, 'rewrite_rules']);
        add_filter('redirect_canonical', [$this, 'canonical'], 10, 2);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('wp_head', [$this, 'wpHead']);
    }

/**
 * 必要なデータが用意されていればtrueを返す
 * enqueueScripts, wpHeadでそれぞれタグを出力するかのフラグとする
 * @return [type] [description]
 */
    private function valid()
    {
        return true;
    }

    public function enqueueScripts()
    {
        if ($this->valid()) {
            wp_enqueue_script('pwa-firebase', 'https://www.gstatic.com/firebasejs/4.1.3/firebase.js', [], null, true);
            wp_enqueue_script('pwa-register', home_url('/pwa-register.js'), ['pwa-firebase'], VERSION, true);
        }

        wp_localize_script('pwa-register', 'WP_REGISTER_SERVICE_WORKER', [
            'root' => esc_url_raw(rest_url()),
            'base64' => base64_encode($this->customizer->get_theme_mod('application-user') . ':' . $this->customizer->get_theme_mod('application-password'))
        ]);
    }

    public function wpHead()
    {
        if ($this->valid()) {
            echo '<link rel="manifest" href="', home_url('/pwa-manifest.json'), '">';
        }
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
