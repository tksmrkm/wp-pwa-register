<?php

namespace WpPwaRegister;

class Logs
{
    use Singleton;

    public function init()
    {
        $this->log_dir = ROOT . DS . 'logs';
    }

    public function debug()
    {
        $this->logging('debug', func_get_args());
    }

    private function logging($flag = 'debug', $args)
    {
        if (!file_exists($this->log_dir)) {
            mkdir($this->log_dir);
        }

        $log_file_path = $this->log_dir . DS . strtolower($flag) . '.log';

        $backtrace = debug_backtrace(false, 2);
        $fp = fopen($log_file_path, 'a');
        $header = strtoupper($flag) . ' :: ' . $backtrace[1]['file'] . '(' . $backtrace[1]['line'] . ') - ' . date_i18n('Y-m-d H:i:s') . PHP_EOL;
        fwrite($fp, $header);
        foreach ($args as $value) {
            fwrite($fp, var_export($value, true) . PHP_EOL . PHP_EOL);
        }
        fclose($fp);
    }
}