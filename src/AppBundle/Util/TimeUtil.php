<?php

namespace AppBundle\Util;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalResidence;

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
        $dayOfDateTime = self::getDayOfDateTime($dateTime); //This is a new DateTime object
        return $dayOfDateTime->add(new \DateInterval('P1D')); //add one day
    }


    /**
     * @param \DateTime $dateOfBirth
     * @param \DateTime $dateOfDeath
     * @return int
     */
    public static function getAgeYear($dateOfBirth, $dateOfDeath = null)
    {
        
        
        if($dateOfDeath != null) {
            $endDate = $dateOfDeath;
        } else {
            $endDate = new \DateTime('now');
        }

        $interval = $endDate->diff($dateOfBirth);

        return $interval->y;
    }


    /**
     * @param \DateTime $dateOfBirth
     * @param \DateTime $measurementDate
     * @return int
     */
    public static function getAgeInDays($dateOfBirth, $measurementDate)
    {
        $measurementDate = clone $measurementDate;
        $dateOfBirth = clone $dateOfBirth;
        $dateInterval = $measurementDate->diff($dateOfBirth);
        return $dateInterval->days;
    }


    /**
     * @param \DateTime $dateOfBirth
     * @param \DateTime $latestLitterDate
     * @return int
     */
    public static function ageInSystemForProductionValue($dateOfBirth, $latestLitterDate)
    {
        if($dateOfBirth == null || $latestLitterDate == null) { return null; }
        return self::getAgeYear($dateOfBirth, $latestLitterDate);
    }

    
    /**
     * @param \DateTime $dateOfBirth
     * @param \DateTime $earliestLitterDate
     * @return boolean
     */
    public static function isGaveBirthAsOneYearOld($dateOfBirth, $earliestLitterDate)
    {
        if($dateOfBirth == null || $earliestLitterDate == null) { return false; }

        $months = 18;
        $oneYearOldLimit = clone $dateOfBirth;
        $oneYearOldLimit->add(new \DateInterval('P' . $months . "M"));

        if($oneYearOldLimit < $earliestLitterDate) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * FIXME the last 3 digits likely represent the timezone. So the time might be of a few hours.
     *
     * @param string $awsSqsTimestamp
     * @return \DateTime
     */
    public static function getDateTimeFromAwsSqsTimestamp($awsSqsTimestamp)
    {
        if($awsSqsTimestamp != null) {
            if(strlen($awsSqsTimestamp) == 13) {
                return (new \DateTime())->setTimestamp(substr($awsSqsTimestamp, 0, 10));
            }
        }
        return null;
    }


    /**
     * @param string $format
     * @return string
     */
    public static function getTimeStampToday($format = 'Y-m-d')
    {
        return self::getTimeStampNow($format);
    }


    /**
     * @param string $format
     * @return string
     */
    public static function getTimeStampNow($format = 'Y-m-d_H:i:s')
    {
        return (new \DateTime())->format($format);
    }


    /**
     * @param string $date
     * @return string
     */
    public static function flipDateStringOrder($date)
    {
        $dateParts = explode('-', $date);
        return implode('-',array_reverse($dateParts));
    }


    /**
     * DateString format should be YYYY-MM-DD, where MM and DD can also be one digit in length 
     * 
     * @param string $dateString
     * @return bool
     */
    public static function isFormatYYYYMMDD($dateString)
    {
        return (bool)preg_match("/^[0-9]{4}-((0[1-9]|1[0-2])|[1-9])-((0[1-9]|[1-2][0-9]|3[0-1])|[1-9])$/",$dateString);
    }
}