<?php

namespace WpPwaRegister;

use WpPwaRegister\Notifications\Subscribe;

class Plugin
{
    const USERNAME = 'wp-pwa-register';
    const OPTION_NAME = 'wp-pwa-register_admin-user-id';

    private $valid = null;
    private $customizer;
    private Register $register;
    private ServiceWorker $sw;
    private Manifest $manifest;
    private Users $users;

    private function prepare()
    {
        $container = [];
        $container['customizer'] = Customizer::getInstance();
        $container['logs'] = Logs::getInstance();
        $container['subscribe'] = new Subscribe($container['customizer'], $container['logs']);
        Api::getInstance($container);
        $this->manifest = new Manifest($container['customizer']);
        $this->register = new Register([$this, 'callable_valid']);
        $this->sw = new ServiceWorker($container['customizer']);
        $this->users = new Users($container['customizer'], $container['subscribe'], $container['logs']);

        Firebase::getInstance($container['customizer']);
        Notifications\Notifications::getInstance($container);
        new Notifications\Post();
        new Notifications\NotificationInstance($container['logs'], $container['customizer']);
        new Notifications\Option($container['customizer'], $container['subscribe'], $container['logs']);
        new Notifications\NotificationHttpV1($container['logs'], $container['customizer']);
        MetaBoxes\PushFlag::getInstance();
        Head::getInstance($container);

        $this->customizer = $container['customizer'];
    }

    public function __construct($file)
    {
        $this->prepare();

        register_deactivation_hook($file, [$this, 'deactivate']);
        register_activation_hook($file, [$this, 'activate']);
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

    public function callable_valid()
    {
        return $this->valid();
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
        wp_localize_script('pwa-register', 'WP_REGISTER_SERVICE_WORKER', [
            'webroot' => get_home_url(),
            'root' => esc_url_raw(rest_url()),
            'base64' => base64_encode(self::USERNAME . ':' . $this->customizer->get_theme_mod('application-password')),
            'debug' => $this->customizer->get_theme_mod('debug', false),
            'register' => [
                'useDialog' => $this->customizer->get_theme_mod('using-register-dialog', false),
                'icon' => $this->customizer->get_theme_mod('register-icon', false),
                'message' => $this->customizer->get_theme_mod('register-message', false)
            ]
        ]);

        wp_localize_script('pwa-register', 'WP_PWA_REGISTER_FIREBASE_CONFIG', [
            'appId' => $this->customizer->get_theme_mod('app-id'),
            'apiKey' => $this->customizer->get_theme_mod('api-key'),
            'projectId' => $this->customizer->get_theme_mod('project-id'),
            'senderId' => $this->customizer->get_theme_mod('sender-id'),
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
            unlink($filename);
        } else {
            $userId = get_option(self::OPTION_NAME);
            delete_option(self::OPTION_NAME);
        }
        remove_role(self::USERNAME);
        wp_delete_user($userId);
    }

    private function createUser()
    {
        add_role(self::USERNAME, __('PWA Users 管理'), [
            'read' => true,
            'manage_pwa_users' => true
        ]);
        $password = wp_generate_password(12, true, true);
        $userId = wp_create_user(self::USERNAME, $password, 'pseudo@example.com');
        add_option(self::OPTION_NAME, $userId);
        wp_update_user([
            'ID' => $userId,
            'role' => self::USERNAME
        ]);
    }

    public function rewrite_rules()
    {
        $this->register->registerRoute();
        $this->sw->registerRoute();
        $this->manifest->registerRoute();
        $this->users->registerRoute();
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
