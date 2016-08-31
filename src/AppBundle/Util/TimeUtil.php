<?php

namespace AppBundle\Util;


class TimeUtil
{

    /**
     * @param \DateTime $dateTime1
     * @param \DateTime $dateTime2
     * @return bool
     */
    public static function isDateTimesOnTheSameDay(\DateTime $dateTime1, \DateTime $dateTime2)
    {
        if(self::getDayOfDateTime($dateTime1) == self::getDayOfDateTime($dateTime2)){
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param \DateTime $dateTime
     * @return \DateTime
     */
    public static function getDayOfDateTime(\DateTime $dateTime)
    {
        $dateTimeStringWithoutTime = $dateTime->format('Y-m-d');
        return new \DateTime($dateTimeStringWithoutTime);
    }


    /**
     * @param \DateTime $dateTime
     * @return \DateTime
     */
    public static function getDayAfterDateTime(\DateTime $dateTime)
    {
        $dayOfDateTime = self::getDayOfDateTime($dateTime);
        return $dayOfDateTime->add(new \DateInterval('P1D')); //add one day
    }
}