<?php

namespace WpPwaRegister\Notifications;

trait TraitChoose
{
    /**
     * @deprecated
     */
    private function choose($left, $right)
    {
        if ($left === '') {
            return $right;
        }

        return $left;
    }

}