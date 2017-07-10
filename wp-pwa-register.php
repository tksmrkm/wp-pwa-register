<?php

namespace WpPwaRegister;

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

new Plugin(__FILE__);

Manifest::getInstance();
Customizer::getInstance();
Register::getInstance();
ServiceWorker::getInstance();
