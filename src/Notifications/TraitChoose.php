<?php

namespace WpPwaRegister\Notifications;

use WpPwaRegister\Logs;

trait TraitChoose
{
    /**
     * @deprecated
     */
    private function choose($left, $right)
    {
        if ($left === '') {
            $this->logs->debug('Choose method still uses right value');
            return $right;
        }

        return $left;
    }

    private Logs $logs;
}