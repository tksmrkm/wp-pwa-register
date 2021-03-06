<?php

namespace WpPwaRegister;

class Logs
{
    use traits\Singleton;
    
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
        $upper_flag = strtoupper($flag);
        $escaped_root = str_replace('\\', '\\\\', ROOT);
        $path = preg_replace("/^${escaped_root}/", '', $backtrace[1]['file']);
        $line = $backtrace[1]['line'];
        $date = date_i18n('Y-m-d H:i:s');
        $eol = PHP_EOL;
        $header = "${upper_flag} :: ${path}(${line}) [${date}]${eol}";
        fwrite($fp, $header);
        foreach ($args as $value) {
            fwrite($fp, var_export($value, true) . PHP_EOL . PHP_EOL);
        }
        fclose($fp);
    }
}