<?php

namespace WpPwaRegister\Notifications;

use WpPwaRegister\Customizer;
use WpPwaRegister\Logs;
use WpPwaRegister\Option;
use WpPwaRegister\Users;

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

    public function _migrate_view()
    {
        global $wpdb;

        $pwa_users = $this->customizer->get_theme_mod(Users::CUSTOMIZER_SLUG_KEY, Users::POST_SLUG);
        $max_count = Users::FCM_BATCH_MAX_COUNT;
        $resubscribed_key = '_resubscribed';

        $query = <<<QUERY
        SELECT
            Post.ID as id,
            Post.post_title as title,
            Token.meta_value as token,
            Done.meta_value as Done
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
            AND
            Done.meta_value IS NULL
        GROUP BY
            Token.meta_value
        ORDER BY
            Post.ID
        DESC
        LIMIT {$max_count}
        QUERY;
        $queried_results = $wpdb->get_results($query);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (count($queried_results)) {
                $ids = array_map(function($row) {
                    return $row->id;
                }, $queried_results);
                $tokens = array_map(function($row) {
                    return $row->token;
                }, $queried_results);

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
            } else {
                echo 'nothing to exec';
            }
        } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $action_url = menu_page_url(self::SUBMENU_KEY, false);
            $count = count($queried_results);
            echo '<form action="' .$action_url . '" method="post"><input type="submit" value="実行 (' . $count . '件)"></form>';
        }
    }
}