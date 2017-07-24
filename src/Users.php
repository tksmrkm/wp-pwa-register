<?php

namespace WpPwaRegister;

class Users
{
    use Singleton;

    public function init()
    {
        add_action('rest_api_init', [$this, 'restApiInit']);
    }

    public function restApiInit()
    {
        $result = register_rest_field('pwa_users', 'token', [
            'update_callback' => function($value, $object, $field_name) {
                update_post_meta($object->ID, 'token', $value);
            }
        ]);
    }

}