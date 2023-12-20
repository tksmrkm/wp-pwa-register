<?php

namespace WpPwaRegister;

use Monolog\ErrorHandler;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class Logs
{
    use traits\Singleton;
    private $log_dir;
    private Logger $monolog;

    public function init()
    {
        $monolog = new Logger('Log');
        $exception = new Logger('Exception');
        $handler = new RotatingFileHandler(ROOT . DS . 'logs' . DS . 'debug.log', 7);
        $log_format = "%datetime% > %context.file%::%context.line% %extra%\n%level_name% > %message%\n\n";
        $formatter = new LineFormatter($log_format, 'Y-m-d H:i:s', true, true);
        $handler->setFormatter($formatter);
        $monolog->pushHandler($handler);
        $this->monolog = $monolog;
        $this->log_dir = ROOT . DS . 'logs';

        $exception->pushHandler(
            (new RotatingFileHandler(ROOT . DS . 'logs' . DS . 'exception.log', 7))
            ->setFormatter($formatter)
        );

        ErrorHandler::register($exception);
    }

    public function debug()
    {
        $arg_number = func_num_args();

        if ($arg_number > 0) {
            $backtrace = debug_backtrace();
            $file = str_replace(ROOT . DS, '', $backtrace[0]['file']);
            $line = $backtrace[0]['line'];
            $value = $arg_number > 1 ? var_export(func_get_args(), true): var_export(func_get_arg(0), true);
            $this->monolog->debug($value, [
                'file' => $file,
                'line' => $line
            ]);
        }
    }
}