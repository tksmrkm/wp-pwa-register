<?php

namespace WpPwaRegister\MetaBoxes;

use WpPwaRegister\Singleton;
use const WpPwaRegister\ROOT;
use const WpPwaRegister\DS;


class PushFlag
{
    use Singleton;

    public function init()
    {
        add_action('publish_post', [$this, 'publishPost'], 10, 2);
        add_action('future_post', [$this, 'publishPost'], 10, 2);
        add_action('admin_init', [$this, 'adminInit']);
        add_action('transition_post_status', [$this, 'transitionPostStatus'], 10, 3);
    }

/**
 * post_idに紐づくmetaのキー
 * checkboxのonか空白が入る
 */
    const META_KEY = '_wp-pwa-register-post-push-key';

/**
 * postのstatusが変わるたびに実行される。
 * メタボックスのデータを保存する。
 * @param  string $new  post_status
 * @param  string $old  post_status
 * @param  object $post Postオブジェクト ID: $post->ID
 * @return void
 */
    public function transitionPostStatus($new, $old, $post)
    {
        $save_data = isset($_POST[self::META_KEY]) ? $_POST[self::META_KEY]: false;
        if ($save_data) {
            update_post_meta($post->ID, self::META_KEY, $save_data);
        }
    }

/**
 * メタボックスの登録
 * @return [type] [description]
 */
    public function adminInit()
    {
        add_meta_box('wp-pwa-regisetr-push-with-save-post', 'WEBプッシュを送信', [$this, 'addMetaBox'], 'post');
    }

/**
 * メタボックスの中身
 * /templates/meta_box/web_push.php
 */
    public function addMetaBox()
    {
        $post_id = $_GET['post'];
        $key = self::META_KEY;
        $opts = get_post_meta($post_id, $key, true) ?: [];
        $already = isset($opts['already']) && $opts['already'] ? $opts['already']: false;
        $icon = isset($opts['icon']) && $opts['icon'] === 'on' ? ' checked="checked"': '';
        $headline = isset($opts['headline']) && $opts['headline'] ? $opts['headline']: '';
        $title = isset($opts['title']) && $opts['title'] ? $opts['title']: '';
        $datetime = isset($opts['datetime']) && $opts['datetime'] ? $opts['datetime']: '';
        include_once ROOT . DS . 'templates' . DS . 'meta_boxes' . DS . 'web_push.php';
    }

/**
 * postがpublishのときに実行
 * $post->post_date === $post-post_modifiedで初回の公開時のみに絞込
 */
    public function publishPost($post_id, $post)
    {
        if ($post->post_status === 'future' || $post->post_date === $post->post_modified) {
            $opts = get_post_meta($post_id, self::META_KEY, true);
            $flag = isset($opts['flag']) && $opts['flag'] === 'on' ? true: false;
            if ($flag) {
                $to_publish = true;
                $title = isset($opts['title']) && $opts['title'] ? $opts['title'] : $post->post_title;
                $insert_option = [
                    'post_title' => $title,
                    'post_type' => 'pwa_notifications',
                ];

                $already = isset($opts['already']) && $opts['already'] ? $opts['already']: false;
                if ($already) {
                    $insert_option['ID'] = $already;
                }

                $datetime = isset($opts['datetime']) && $opts['datetime'] ? $opts['datetime']: $post->post_date;
                if ($datetime) {
                    $time = strtotime($datetime);
                    $insert_option['post_date'] = date_i18n('Y-m-d H:i:s', $time);
                    $insert_option['post_date_gmt'] = date('Y-m-d H:i:s', $time);

                    // 未来ならステータスをfutureに
                    if ($time > time()) {
                        $insert_option['post_status'] = 'future';
                        $opts['datetime'] = date_i18n('Y-m-d\TH:i', $time);
                        $to_publish = false;
                    }
                }

                $use_icon = isset($opts['icon']) && $opts['icon'] === 'on' ? true: false;
                $icon_src = '';
                if ($use_icon) {
                    $thumb_id = get_post_thumbnail_id($post_id);
                    $thumb = wp_get_attachment_image_src($thumb_id, [152, 152]);
                    if ($thumb) {
                        $icon_src = $thumb[0];
                    }
                }
                $headline = isset($opts['headline']) && $opts['headline'] ? $opts['headline']: '';

                // pwa_notificationsに記事を準備
                $opts['already'] = wp_insert_post($insert_option);
                update_post_meta($opts['already'], 'icon', $icon_src);
                update_post_meta($opts['already'], 'headline', $headline);
                update_post_meta($opts['already'], 'link', $post->guid);

                // パブリッシュする
                if ($to_publish) {
                    wp_publish_post($notification_id);
                }

                // alreadyフラグをセーブ
                update_post_meta( $post_id, self::META_KEY, $opts);
            }
        }
    }
}