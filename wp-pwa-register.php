<?php

namespace WpPwaRegister;

const ROOT = __DIR__;
const DS = DIRECTORY_SEPARATOR;
const VERSION = '1.0.0';

/*
Plugin Name: WP PWA Register
Description: WordpressにPWA(Progressive Web Apps)を適用させる。
*/

require_once 'src/traits/Singleton.php';
require_once 'src/Plugin.php';
require_once 'src/Manifest.php';
require_once 'src/Customizer.php';
require_once 'src/Register.php';
require_once 'src/ServiceWorker.php';
require_once 'src/Firebase.php';

new Plugin(__FILE__);
