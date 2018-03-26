<?php


namespace AppBundle\Util;


class ProcessUtil
{
    /**
     * @param int $minutes
     */
    public static function setTimeLimitInMinutes($minutes)
    {
        set_time_limit($minutes * 60);
    }
}