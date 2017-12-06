<?php

namespace WpPwaRegister;

const ROOT = __DIR__;
const DS = DIRECTORY_SEPARATOR;
const VERSION = '1.0.3';

/*
Plugin Name: WP PWA Register
Description: WordpressにPWA(Progressive Web Apps)を適用させる。
*/

require_once 'autoload.php';

new Plugin(__FILE__);

/**
 * Filters
 *
 * wp-pwa-register-valid-status
 * on src/Plugin.php Plugin::valid
 */
