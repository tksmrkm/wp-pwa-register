<?php

namespace WpPwaRegister\Notifications;

class Option
{
    const MENU_KEY = 'wp-pwa-register-option';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'adminMenu']);
    }

    public function adminMenu()
    {
        add_menu_page('WP PWA Register', 'WP PWA Register', 'administrator', self::MENU_KEY, [$this, '_view']);
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
}