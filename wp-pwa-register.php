<?php

namespace WpPwaRegister;

const ROOT = __DIR__;
const DS = DIRECTORY_SEPARATOR;
const VERSION = '1.0.2';

/*
Plugin Name: WP PWA Register
Description: WordpressにPWA(Progressive Web Apps)を適用させる。
*/

require_once 'src/traits/Singleton.php';
require_once 'src/Plugin.php';
require_once 'src/Logs.php';
require_once 'src/Manifest.php';
require_once 'src/Customizer.php';
require_once 'src/Register.php';
require_once 'src/ServiceWorker.php';
require_once 'src/Firebase.php';
require_once 'src/Posts.php';
require_once 'src/Notifications.php';
require_once 'src/Users.php';
require_once 'src/Head.php';
require_once 'src/MetaBoxes/PushFlag.php';

new Plugin(__FILE__);

/**
 * Filters
 *
 * wp-pwa-register-valid-status
 * on src/Plugin.php Plugin::valid
 */
