<?php

namespace WpPwaRegister;

trait Singleton
{
    static private $instance;
    private function __construct()
    {
        $this->init();
    }

    private function __clone()
    {

    }

    static public function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init()
    {

    }
}