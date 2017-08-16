<?php

namespace WpPwaRegister;

class Plugin
{
    const USERNAME = 'wp-pwa-register';

    private $valid = null;

    private function prepare()
    {
        $container = [];
        $container['customizer'] = Customizer::getInstance();
        Manifest::getInstance($container);
        Register::getInstance();
        ServiceWorker::getInstance($container);
        Firebase::getInstance($container);
        Posts::getInstance();
        Notifications::getInstance($container);
        Users::getInstance();
        MetaBoxes\PushFlag::getInstance();
        Head::getInstance($container);

        $this->customizer = $container['customizer'];
    }

    public function __construct($file)
    {
        $this->prepare();

        register_deactivation_hook($file, [$this, 'deactivate']);
        register_activation_hook($file, [$this, 'activate']);
        add_action('init', [$this, 'rewrite_rules']);
        add_filter('redirect_canonical', [$this, 'canonical'], 10, 2);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('wp_head', [$this, 'wpHead']);
        add_action('admin_notices', [$this, 'notices']);
    }

/**
 * 管理画面にお知らせを表示する。
 */
    public function notices()
    {
        if (!current_user_can('edit_theme_options')) {
            return false;
        }

        $ip = $this->customizer->get_theme_mod('enable-to-restrict-on-ip', false);
        $loggedin = $this->customizer->get_theme_mod('enable-on-logged-in', false);
        if (!$this->valid() || $ip || $loggedin) {
            echo '<div class="notice notice-info is-dismissible"><p>[WP PWA Register]</p><ul>';

            if ($ip) {
                echo '<li>IPアドレスで制限中(', $this->customizer->get_theme_mod('accepted-ip-address'), ')</li>';
            }

            if ($loggedin) {
                echo '<li>ログインユーザーのみ適用中</li>';
            }

            if (!$this->customizer->get_theme_mod('application-password')) {
                echo '<li>Application Passwordsがありません</li>';
            }

            if (!$this->customizer->get_theme_mod('sender-id')) {
                echo '<li>SenderIDがありません。</li>';
            }

            if (!$this->customizer->get_theme_mod('api-key')) {
                echo '<li>API KEYがありません。</li>';
            }

            if (!$this->customizer->get_theme_mod('project-id')) {
                echo '<li>ProjectIDがありません。</li>';
            }

            if (!$this->customizer->get_theme_mod('server-key')) {
                echo '<li>サーバーキーがありません。</li>';
            }

            if (!$this->customizer->get_theme_mod('icon-src')) {
                echo '<li>ICONのSRCがありません。</li>';
            }

            if (!$this->customizer->get_theme_mod('icon-sizes')) {
                echo '<li>ICONのサイズがありません。</li>';
            }

            if (!$this->customizer->get_theme_mod('icon-type')) {
                echo '<li>ICONのTypeがありません。</li>';
            }

            echo '</ul></div>';
        }
    }

/**
 * 必要なデータが用意されていればtrueを返す
 * enqueueScripts, wpHeadでそれぞれタグを出力するかのフラグとする
 * @return [type] [description]
 */
    private function valid()
    {
        if (!is_null($this->valid)) {
            return $this->valid;
        }
        $this->valid = $this->customizer->get_theme_mod('enable', false)
        && $this->enableOnLoggedIn()
        && $this->enableToRestrictOnIp()
        && $this->customizer->get_theme_mod('application-password')
        && $this->customizer->get_theme_mod('sender-id')
        && $this->customizer->get_theme_mod('api-key')
        && $this->customizer->get_theme_mod('project-id')
        && $this->customizer->get_theme_mod('server-key')
        && $this->customizer->get_theme_mod('icon-src')
        && $this->customizer->get_theme_mod('icon-sizes')
        && $this->customizer->get_theme_mod('icon-type');
        return apply_filters( 'wp-pwa-register-valid-status', $this->valid );
    }

    private function enableToRestrictOnIp()
    {
        $flag = $this->customizer->get_theme_mod('enable-to-restrict-on-ip', false);
        if ($flag) {
            $remote_ip = $_SERVER['REMOTE_ADDR'];
            list($accept_ip, $mask) = explode('/', $this->customizer->get_theme_mod('accepted-ip-address'));
            $accept_long = ip2long($accept_ip) >> (32 - $mask);
            $remote_long = ip2long($remote_ip) >> (32 - $mask);
            return $accept_long === $remote_long;
        }

        return true;
    }

    private function enableOnLoggedIn()
    {
        $flag = $this->customizer->get_theme_mod('enable-on-logged-in', true);

        if ($flag) {
            return is_user_logged_in();
        }

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
            'base64' => base64_encode(self::USERNAME . ':' . $this->customizer->get_theme_mod('application-password'))
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
        $this->createUser();
    }

    public function deactivate()
    {
        $this->deleteUser();
        flush_rewrite_rules();
    }

    private function deleteUser()
    {
        $filename = ROOT . DS . 'userid';
        if (file_exists($filename)) {
            $userId = file_get_contents($filename);
            wp_delete_user($userId);
            unlink($filename);
            remove_role(self::USERNAME);
        }
    }

    private function createUser()
    {
        $username = self::USERNAME;
        add_role($username, __('PWA Users 管理'), [
            'read' => true,
            'manage_pwa_users' => true
        ]);
        $password = wp_generate_password(12, true, true);
        $userId = wp_create_user($username, $password, 'pseudo@example.com');
        wp_update_user([
            'ID' => $userId,
            'role' => $username
        ]);
        $fp = fopen(ROOT . DS . 'userid', 'w');
        fwrite($fp, $userId);
        fclose($fp);
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
