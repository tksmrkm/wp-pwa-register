<?php

namespace WpPwaRegister\Notifications;

use WpPwaRegister\Customizer;
use WpPwaRegister\Logs;
use WpPwaRegister\Option;
use WpPwaRegister\Users;

use const WpPwaRegister\DS;
use const WpPwaRegister\ROOT;

class Resubscription
{
    private Customizer $customizer;
    private Subscribe $subscribe;
    private Logs $logs;

    const SUBMENU_KEY = 'wp-pwa-resubscription';

    public function __construct(Customizer $customizer, Subscribe $subscribe, Logs $logs)
    {
        $this->customizer = $customizer;
        $this->subscribe = $subscribe;
        $this->logs = $logs;
        add_action('admin_menu', [$this, 'adminMenu']);
    }

    public function adminMenu()
    {
        add_submenu_page(Option::MENU_KEY, 'HttpV1 Resubscription', 'HttpV1 Resubscription', 'administrator', self::SUBMENU_KEY, [$this, '_migrate_view']);
    }

    private function createWhere(string $from, string $to): string
    {
        $f = $from ? date('Y-m-d H:i:s', strtotime($from)): false;
        $t = $to ? date('Y-m-d H:i:s', strtotime($to)): false;
        if ($f && $t) {
            return "AND Post.post_date BETWEEN '{$f}' AND '{$t}'";
        } else if ($f) {
            return "AND Post.post_date >= '{$f}'";
        } else if ($to) {
            return "AND Post.post_date <= '{$t}'";
        }

        return "";
    }

    private function createQuery(string $from, string $to, bool $contain_processed = false)
    {
        global $wpdb;

        $pwa_users = $this->customizer->get_theme_mod(Users::CUSTOMIZER_SLUG_KEY, Users::POST_SLUG);
        $max_count = Users::FCM_BATCH_MAX_COUNT;
        $resubscribed_key = '_resubscribed';
        $where = $this->createWhere($from, $to);
        $contain_has_done = $contain_processed ? "": "AND Done.meta_value IS NULL";

        $query = <<<QUERY
        SELECT
            Post.ID as id,
            Post.post_title as title,
            Token.meta_value as token,
            Done.meta_value as processed
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
            {$wpdb->postmeta} as `Done`
                ON Done.post_id = Post.ID
                AND Done.meta_key = '{$resubscribed_key}'
        WHERE
            Post.post_status = 'draft'
            AND
            Deleted.meta_value IS NULL
            AND
            Post.post_type = '{$pwa_users}'
            {$contain_has_done}
            {$where}
        GROUP BY
            Token.meta_value
        ORDER BY
            Post.ID
        DESC
        LIMIT {$max_count}
        QUERY;

        return $query;
    }

    private function migrate(array $items, string $resubscribed_key)
    {
        $ids = array_map(function($row) {
            return $row->id;
        }, $items);
        $tokens = array_map(function($row) {
            return $row->token;
        }, $items);

        $results = $this->subscribe->subscribe($tokens);

        $this->logs->debug($results);

        foreach ($results['result']->results as $index => $result) {
            // error処理, 何もなければ正常処理で_resubscribedフラグを付ける
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
                update_post_meta($ids[$index], $resubscribed_key, true);
            }
        }
    }

    public function _migrate_view()
    {
        global $wpdb;

        $resubscribed_key = '_resubscribed';

        // for template
        $action_url = menu_page_url(self::SUBMENU_KEY, false);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $query = $this->createQuery($_POST['from'] ?? "", $_POST['to'] ?? "");
            $queried_results = $wpdb->get_results($query);
            $count = count($queried_results);

            if ($count > 0) {
                $exec_flag = $_POST['exec'] ?? false;
                $chunks = array_chunk($queried_results, Users::FCM_BATCH_MAX_COUNT);

                if ($exec_flag) {
                    $start = microtime(true);
                    echo '<ul>';
                    foreach ($chunks as $key => $chunk) {
                        set_time_limit(60);
                        $this->migrate($chunk, $resubscribed_key);
                        echo '<li>', $key, ': ', microtime(true) - $start, '</li>';
                    }
                    echo '</ul>';
                    echo '<p>done</p>';
                } else {
                    // for template
                    $unit_count = Users::FCM_BATCH_MAX_COUNT;
                    $chunk_count = count($chunks);

                    include_once ROOT . DS . 'templates' . DS . 'notifications' . DS . 'resubscribe_exec.php';
                }
            } else {
                echo 'nothing to exec';
            }
        } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            include_once ROOT . DS . 'templates' . DS . 'notifications' . DS . 'resubscribe.php';
        }
    }
}