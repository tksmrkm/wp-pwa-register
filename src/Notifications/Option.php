<?php

namespace WpPwaRegister\Notifications;

use WpPwaRegister\Customizer;
use WpPwaRegister\Logs;
use WpPwaRegister\Users;

use const WpPwaRegister\DS;
use const WpPwaRegister\ROOT;

class Option
{
    const MENU_KEY = 'wp-pwa-register-option';
    const MIGRATE_MENU_KEY = 'wp-pwa-register-user-migration';

    private Customizer $customizer;
    private Subscribe $subscribe;
    private Logs $logs;

    public function __construct(Customizer $customizer, Subscribe $subscribe, Logs $logs)
    {
        $this->customizer = $customizer;
        $this->subscribe = $subscribe;
        $this->logs = $logs;
        add_action('admin_menu', [$this, 'adminMenu']);
    }

    public function adminMenu()
    {
        add_menu_page('WP PWA Register', 'WP PWA Register', 'administrator', self::MENU_KEY, [$this, '_view']);
        add_submenu_page(self::MENU_KEY, 'User migrate to Http v1', 'User migrate to Http v1', 'administrator', self::MIGRATE_MENU_KEY, [$this, '_migrate_view']);
    }

    public function _view()
    {
        echo '<div class="wrap"><h2>削除用コールバックページ</h2>';

        if (isset($_GET['post_id'])) {
            $post_id = $_GET['post_id'];

            $post = get_post($post_id);

            if ($post) {
                // get NotificationInstances IDs
                $ids = get_post_meta($post_id, '_' . NotificationInstance::POST_KEY, true);

                // delete Notifications
                echo '<h3>delete: ', $post_id, '</h3>';
                echo '<p>';
                $result = wp_delete_post($post_id, true);
                echo $result ? 'success': 'failure';
                echo '</p>';

                if ($ids) {
                    $ids = explode(',', $ids);

                    // delete NotificationInstances
                    foreach ($ids as $id) {
                        echo '<h3>delete: ', $id, '</h3>';
                        echo '<p>';
                        $result = wp_delete_post($id, true);
                        echo $result ? 'success': 'failure';
                        echo '</p>';
                    }
                }
            } else {
                echo $post_id, ' is not exist';
            }

        }

        echo '</div>';
    }

    public function _migrate_view()
    {
        global $wpdb;
        $api_version_key = Users::META_API_VERSION_KEY;
        $pwa_users = $this->customizer->get_theme_mod(Users::CUSTOMIZER_SLUG_KEY, Users::POST_SLUG);

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $legacy_query = <<<QUERY
            SELECT
                count(DISTINCT Token.meta_value) as count
            FROM
                {$wpdb->posts} as `Post`
            LEFT JOIN
                {$wpdb->postmeta} as `Token`
                    ON Token.post_id = Post.ID
                    AND Token.meta_key = 'token'
            LEFT JOIN
                {$wpdb->postmeta} as `Deleted`
                    ON Deleted.post_id = Post.ID
                    AND Deleted.meta_key = 'deleted'
            LEFT JOIN
                {$wpdb->postmeta} as `Version`
                    ON Version.post_id = Post.ID
                    AND Version.meta_key = '{$api_version_key}'
            WHERE
                Post.post_status = 'draft'
                AND
                Deleted.meta_value IS NULL
                AND
                Post.post_type = '{$pwa_users}'
                AND
                Version.meta_value IS NULL
            ORDER BY
                Post.ID
            DESC
            QUERY;
            $v2_query = <<<QUERY
            SELECT
                count(DISTINCT Token.meta_value) as count
            FROM
                {$wpdb->posts} as `Post`
            LEFT JOIN
                {$wpdb->postmeta} as `Token`
                    ON Token.post_id = Post.ID
                    AND Token.meta_key = 'token'
            LEFT JOIN
                {$wpdb->postmeta} as `Deleted`
                    ON Deleted.post_id = Post.ID
                    AND Deleted.meta_key = 'deleted'
            LEFT JOIN
                {$wpdb->postmeta} as `Version`
                    ON Version.post_id = Post.ID
                    AND Version.meta_key = '{$api_version_key}'
            WHERE
                Post.post_status = 'draft'
                AND
                Deleted.meta_value IS NULL
                AND
                Post.post_type = '{$pwa_users}'
                AND
                Version.meta_value = 'v2'
            ORDER BY
                Post.ID
            DESC
            QUERY;
            $legacy_user_results = $wpdb->get_results($legacy_query);
            $legacy_user_count = count($legacy_user_results) ? $legacy_user_results[0]->count: 0;
            $migrated_user_results = $wpdb->get_results($v2_query);
            $migrated_user_count = count($migrated_user_results) ? $migrated_user_results[0]->count: 0;

            $max_count = Users::FCM_BATCH_MAX_COUNT;
            $exec_count = $legacy_user_count > $max_count ? $max_count: $legacy_user_count;
            $action_url = menu_page_url(self::MIGRATE_MENU_KEY, false);

            include_once ROOT . DS . 'templates' . DS . 'notifications' . DS . 'migrate.php';
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $query = <<<QUERY
            SELECT
                Post.ID as id,
                Token.meta_value as token,
                Version.meta_value as version
            FROM
                {$wpdb->posts} as `Post`
            LEFT JOIN
                {$wpdb->postmeta} as `Deleted`
                    ON Deleted.post_id = Post.ID
                    AND Deleted.meta_key = 'deleted'
            LEFT JOIN
                {$wpdb->postmeta} as `Token`
                    ON Token.post_id = Post.ID
                    AND Token.meta_key = 'token'
            LEFT JOIN
                {$wpdb->postmeta} as `Version`
                    ON Version.post_id = Post.ID
                    AND Version.meta_key = '{$api_version_key}'
            WHERE
                Post.post_status = 'draft'
                AND
                Deleted.meta_value IS NULL
                AND
                Post.post_type = '{$pwa_users}'
                AND
                Version.meta_value IS NULL
            GROUP BY
                Token.meta_value
            ORDER BY
                Post.ID
            DESC
            LIMIT {$_POST['exec_count']}
            QUERY;

            $queried_results = $wpdb->get_results($query);

            if (count($queried_results)) {
                $ids = array_map(function($row) {
                    return $row->id;
                }, $queried_results);
                $tokens = array_map(function($row) {
                    return $row->token;
                }, $queried_results);

                $results = $this->subscribe->subscribe($tokens);

                foreach ($results['result']->results as $index => $result) {
                    // error処理, 何もなければ正常処理でv2フラグを付ける
                    if (isset($result->error)) {
                        /*
                        NOT_FOUND - 登録トークンが削除されたか、アプリがアンインストールされています。
                        INVALID_ARGUMENT - 指定された登録トークンが、送信者 ID に対して有効でない。
                        INTERNAL - 不明な理由によりバックエンド サーバーでエラーが発生しました。リクエストを再試行します。
                        TOO_MANY_TOPICS - アプリ インスタンスあたりのトピック数が多すぎます。
                        RESOURCE_EXHAUSTED - 短期間での登録リクエストまたは登録解除リクエストが多すぎます。指数バックオフを使用して再試行する。
                         */

                         $this->logs->debug([
                            'index' => $index,
                            'result' => $result,
                            'id' => $ids[$index]
                         ]);

                        if (
                            $result->error === 'NOT_FOUND'
                            || $result->error === 'INVALID_ARGUMENT'
                        ) {
                            update_post_meta($ids[$index], 'deleted', true);
                        }
                    } else {
                        update_post_meta($ids[$index], Users::META_API_VERSION_KEY, 'v2');
                    }
                }
            }
        }
    }
}