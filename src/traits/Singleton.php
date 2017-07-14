<?php

namespace WpPwaRegister;

trait Singleton
{
    static private $instance;
    private function __construct($container)
    {
        $this->init($container);
    }

    private function __clone()
    {

    }

    static public function getInstance($container = null)
    {
        if (!self::$instance) {
            self::$instance = new self($container);
        }

        return self::$instance;
    }

    public function init($container)
    {
    }
}