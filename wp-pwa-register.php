<?php

namespace WpPwaRegister;

const ROOT = __DIR__;
const PLUGIN_FILE = __FILE__;
const DS = DIRECTORY_SEPARATOR;
$pkg = json_decode(file_get_contents(ROOT . DS . 'package.json'));
define('VERSION', $pkg->version);

/*
Plugin Name: WP PWA Register
Description: WordpressにPWA(Progressive Web Apps)を適用させる。
Version: 1.8.4
*/

require_once ROOT . DS . 'vendor' . DS . 'autoload.php';

new Plugin(PLUGIN_FILE);

/**
 * Filters
 *
 * wp-pwa-register-valid-status
 * on src/Plugin.php Plugin::valid
 */
