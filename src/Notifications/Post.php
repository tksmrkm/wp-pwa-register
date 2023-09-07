<?php

namespace WpPwaRegister\Notifications;

class Post
{
    const POST_SLUG = 'pwa_notifications';
    const PREPARE_COLUMN_KEY = '_reach_prepare';
    const RESULT_COLUMN_KEY = '_reach_result';
    const REACH_SUCCESS_KEY = '_reach_success';
    const REACH_ERROR_KEY = '_reach_error';
    const REACH_DELETED_KEY = '_reach_deleted';

    public function __construct()
    {
        add_action('init', [$this, 'register']);
        add_action('manage_posts_custom_column', [$this, 'addCustomColumn'], 10, 2);
        add_filter('manage_edit-pwa_notifications_columns', [$this, 'firebaseNotificationsManageColumns']);
    }

    private function get_reached_number($post_id, $key)
    {
        $ids = get_post_meta($post_id, '_' . NotificationInstance::POST_KEY, true)
            ?: get_post_meta($post_id, NotificationInstance::POST_KEY, true);

        if ($ids) {
            $ids = explode(',', $ids);
            return array_sum(
                array_map(function($id) use ($key) {
                    $value = get_post_meta($id, $key, true) ?? 0;
                    return (int)$value;
                }, $ids)
            );
        }

        return get_post_meta($post_id, $key, true) ?: '-';
    }

    public function addCustomColumn($column, $post_id)
    {
        if ($column === self::RESULT_COLUMN_KEY) {
            $success = $this->get_reached_number($post_id, self::REACH_SUCCESS_KEY);
            $error = $this->get_reached_number($post_id, self::REACH_ERROR_KEY);
            $deleted = $this->get_reached_number($post_id, self::REACH_DELETED_KEY);

            echo "{$success} / {$error} / {$deleted}";
        }

        if ($column === self::PREPARE_COLUMN_KEY) {
            $ids = get_post_meta($post_id, '_' . NotificationInstance::POST_KEY, true)
                ?: get_post_meta($post_id, NotificationInstance::POST_KEY, true);
            $ids = explode(',', $ids);
            $count = array_filter($ids, function($id) {
                return !get_post_meta($id, NotificationInstance::PUBLISHED_FLAG_KEY, true);
            });
            echo count($count);
        }
    }

    public function firebaseNotificationsManageColumns($columns)
    {
        $columns[self::RESULT_COLUMN_KEY] = '送信 / エラー / 削除';
        $columns[self::PREPARE_COLUMN_KEY] = '送信待ち';

        return $columns;
    }

    public function register()
    {
        register_post_type( self::POST_SLUG, [
            'label' => 'PUSH通知',
            'labels' => [
                'name' => 'PUSH通知',
                'singular_name' => 'PUSH通知',
            ],
            'description' => 'PUSH通知の登録',
            'public' => false,
            'rewrite' => false,
            'show_in_rest' => true,
            'show_ui' => true,
            'supports' => [
                'title',
                'custom-fields'
            ],
            'capabilities' => [
                'read_post' => 'read',
                'edit_post' => 'edit_others_posts',
                'edit_posts' => 'edit_others_posts',
                'delete_post' => 'delete_others_posts',
                'delete_posts' => 'delete_others_posts',
                'edit_others_posts' => 'edit_others_posts',
                'publish_posts' => 'publish_posts',
                'read_private_posts' => 'read_private_posts',
            ]
        ] );
    }
}
