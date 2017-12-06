<?php

namespace WpPwaRegister;

spl_autoload_register(function($class) {
    $pattern = '/^WpPwaRegister\\\\/';

    $src_dir = 'src';

    if (preg_match($pattern, $class)) {
        $basename = preg_replace($pattern, '', $class);
        $basename = preg_replace('/\\\\/', DS, $basename);
        require_once ROOT . DS . $src_dir . DS . $basename . '.php';
    }
});
