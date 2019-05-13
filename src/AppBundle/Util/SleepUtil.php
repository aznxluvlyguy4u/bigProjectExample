<?php


namespace AppBundle\Util;


class SleepUtil
{
    /**
     * @param float $seconds
     */
    public static function sleep(float $seconds) {
        usleep(intval($seconds * 1000000));
    }
}