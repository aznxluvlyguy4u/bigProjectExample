<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use Symfony\Component\Validator\Constraints\DateTime;

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
     * @return bool
     */
    public static function isDateInFuture(\DateTime $dateTime) {
        $timeIntervalInDaysFromNow = TimeUtil::getDaysBetween(new \DateTime(), $dateTime);
        return $timeIntervalInDaysFromNow > 0;
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
            $endDate = clone $dateOfDeath;
        } else {
            $endDate = new \DateTime('now');
        }

        $interval = $endDate->diff($dateOfBirth);

        return $interval->y;
    }


    /**
     * @param \DateTime $dateOfBirth
     * @param \DateTime $dateOfDeath
     * @return int
     */
    public static function getAgeMonths($dateOfBirth, $dateOfDeath = null)
    {


        if ($dateOfDeath != null) {
            $endDate = clone $dateOfDeath;
        } else {
            $endDate = new \DateTime('now');
        }

        $interval = $endDate->diff($dateOfBirth);

        return $interval->y * 12 + $interval->m;
    }


    /**
     * @param \DateTime|null $date1
     * @param \DateTime|null $date2
     * @return bool
     */
    public static function isDate1BeforeDate2(?\DateTime $date1, ?\DateTime $date2): bool
    {
        return $date1 && $date2 && self::getDaysBetween($date1, $date2) > 0;
    }


    /**
     * Warning! This is a non-signed result!
     *
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
     * Get the date count between two dates, positive range means $dt2 is still ahead, 
     * negative ranges means $dt2 date has surpassed $dt1.
     * 
     * @param \DateTime $dt1
     * @param \DateTime $dt2
     * @return bool|mixed
     */
    public static function getDaysBetween(\DateTime $dt1, \DateTime $dt2){
        if(!$dt1 || !$dt2){
            return false;
        }
        $dt1Clone = clone $dt1;
        $dt2Clone = clone $dt2;
        $dt1Clone->setTime(0,0,0);
        $dt2Clone->setTime(0,0,0);

        // DateInterval
        $dti = $dt1Clone->diff($dt2Clone);

        // nb: ->days always positive
        return $dti->days * ( $dti->invert ? -1 : 1);   
    }

    /**
     * Check if a given date is in between a given date range, returns true if it is 
     * in the date range.
     * @param  $date
     * @param  $startDate
     * @param  $endDate
     * @return bool
     */
    public static function isDateBetweenDates( $date, $startDate, $endDate) {
        return $date >= $startDate && $date <= $endDate;
    }
    
    /**
     * @param \DateTime $dateOfBirth
     * @param \DateTime $latestLitterDate
     * @return int
     */
    public static function ageInSystemForProductionValue($dateOfBirth, $latestLitterDate)
    {
        if($dateOfBirth == null || $latestLitterDate == null) { return null; }


        $endDate = clone $latestLitterDate;
        $interval = $endDate->diff($dateOfBirth);

        $months = $interval->m;
        $years = $interval->y;

        if($months < 6) {
            return $years;
        } else {
            return $years + 1;
        }
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
     * FIXME the last 3 digits likely represent the timezone. So the time might be off a few hours.
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
     * @param string $format
     * @return string
     */
    public static function getTimeStampNowForFiles($format = 'Y-m-d_H\ui\ms\s')
    {
        return (new \DateTime())->format($format);
    }


    /**
     * @param \DateTime $dateTime
     * @param string $format
     * @return string
     */
    public static function getTimeStampForSql($dateTime, $format = 'Y-m-d H:i:s')
    {
        return $dateTime != null ? $dateTime->format($format) : null;
    }


    /**
     * @param \DateTime $dateTime
     * @param int $timeZoneHoursOffset
     * @return string
     */
    public static function getTimeStampForJsonBody($dateTime, $timeZoneHoursOffset = 1)
    {
        if (!$dateTime) { return null; }
        $timeZoneHours = str_pad($timeZoneHoursOffset,2,'0', STR_PAD_LEFT);
        return $dateTime->format('Y-m-d\TH\:i\:s\+'.$timeZoneHours.':00');
    }


    /**
     * @param string $dateString
     * @param bool $includeTime
     * @return null|string
     */
    public static function getTimeStampForSqlFromAnyDateString($dateString, $includeTime = true)
    {
        if (is_string($dateString)) {
            if (DateUtil::isFormatYYYYMMDD($dateString) || DateUtil::isFormatDDMMYYYY($dateString)) {
                if ($includeTime) {
                    return TimeUtil::getTimeStampForSql(new \DateTime($dateString));
                } else {
                    return TimeUtil::getTimeStampForSql(new \DateTime($dateString), 'Y-m-d');
                }
            }
        }
        return null;
    }


    public static function getLogDateString()
    {
        return self::getTimeStampNow('Y-m-d H:i:s');
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
     * Regex for YYYY-MM-DD, where MM and DD can also be one digit in length
     *
     * @param bool $mustHaveLeadingZeroes
     * @return string
     */
    public static function getYYYYMMDDRegex($mustHaveLeadingZeroes = false)
    {
        if($mustHaveLeadingZeroes) {
            return "/^[0-9]{4}-((0[1-9]|1[0-2]))-((0[1-9]|[1-2][0-9]|3[0-1]))$/";
        }
        return "/^[0-9]{4}-((0[1-9]|1[0-2])|[1-9])-((0[1-9]|[1-2][0-9]|3[0-1])|[1-9])$/";
    }

    /**
     * DateString format should be YYYY-MM-DD, where MM and DD can also be one digit in length
     *
     * @param string $dateString
     * @param boolean $mustHaveLeadingZeroes
     * @return bool
     */
    public static function isFormatYYYYMMDD($dateString, $mustHaveLeadingZeroes = false)
    {
        return (bool)preg_match(self::getYYYYMMDDRegex($mustHaveLeadingZeroes),$dateString);
    }


    /**
     * @param string $dateTime
     * @param string $format
     * @return bool
     */
    public static function isValidDateTime($dateTime, $format = SqlUtil::DATE_FORMAT)
    {
        $d = \DateTime::createFromFormat($format, $dateTime);
        return $d && $d->format($format) == $dateTime;
    }


    /**
     * Regex for DD-MM-YYYY, where MM and DD can also be one digit in length
     *
     * @param bool $mustHaveLeadingZeroes
     * @return string
     */
    public static function getDDMMYYYYRegex($mustHaveLeadingZeroes = false)
    {
        if($mustHaveLeadingZeroes) {
            return "/^((0[1-9]|[1-2][0-9]|3[0-1]))-((0[1-9]|1[0-2]))-[0-9]{4}$/";
        }
        return "/^((0[1-9]|[1-2][0-9]|3[0-1])|[1-9])-((0[1-9]|1[0-2])|[1-9])-[0-9]{4}$/";
    }

    /**
     * DateString format should be DD-MM-YYYY, where MM and DD can also be one digit in length
     *
     * @param string $dateString
     * @param boolean $mustHaveLeadingZeroes
     * @return bool
     */
    public static function isFormatDDMMYYYY($dateString, $mustHaveLeadingZeroes = false)
    {
        return (bool)preg_match(self::getDDMMYYYYRegex($mustHaveLeadingZeroes),$dateString);
    }


    /**
     * @param string $dateTimeString
     * @return bool|string
     */
    public static function getYearFromDateTimeString($dateTimeString) {
        $parts = explode('-', $dateTimeString);
        if(!$parts) {
            return false;
        } else {
            return $parts[0];
        }
    }


    /**
     * @param string $flippedDateString
     * @return \DateTime|null
     */
    public static function getDateTimeFromFlippedAndNullCheckedDateString($flippedDateString)
    {
        return self::getDateTimeFromNullCheckedDateString(TimeUtil::flipDateStringOrder($flippedDateString));
    }


    /**
     * @param string $dateString
     * @return \DateTime|null
     */
    public static function getDateTimeFromNullCheckedDateString($dateString)
    {
        return $dateString != null ? new \DateTime($dateString) : null;
    }


    /**
     * @param string|int $keyForDateTimeString
     * @param array $array
     * @param string $nullFiller
     * @return DateTime|string
     */
    public static function getDateTimeFromNullCheckedArrayValue($keyForDateTimeString, $array, $nullFiller = null)
    {
        $measurementDate = TimeUtil::getDateTimeFromNullCheckedDateString($array[$keyForDateTimeString]);
        return Utils::fillNullOrEmptyString($measurementDate, $nullFiller);
    }


    /**
     * Fill the D-M-Y or Y-M-D with leading zeros and flips them in the correct order
     * into the YYYY-MM-DD format.
     *
     * @param $dateString
     * @return null|string
     */
    public static function fillDateStringWithLeadingZeroes($dateString)
    {
        if(!is_string($dateString)) { return null; }

        $parts = explode('-', $dateString);

        if(count($parts) != 3) { return null; }

        if(strlen($parts[0]) == 4) {
            $dateString = str_pad($parts[0],4,'0', STR_PAD_LEFT).'-'
                         .str_pad($parts[1],2,'0', STR_PAD_LEFT).'-'
                         .str_pad($parts[2],2,'0', STR_PAD_LEFT);

        } else if(strlen($parts[2]) == 4) {
            $dateString = str_pad($parts[2],4,'0', STR_PAD_LEFT).'-'
                         .str_pad($parts[1],2,'0', STR_PAD_LEFT).'-'
                         .str_pad($parts[0],2,'0', STR_PAD_LEFT);
        } else {
            return null;
        }

        return self::isFormatYYYYMMDD($dateString, true) ? $dateString : null;
    }


    /**
     * Change string with dateFormat of MM/DD/YYYY to YYYY-MM-DD
     *
     * @param string $americanDate
     * @return string
     */
    public static function changeDateFormatStringFromAmericanToISO($americanDate)
    {
        $dateParts = explode('/', $americanDate);
        return str_pad($dateParts[2],4,'0', STR_PAD_LEFT).'-'
        .str_pad($dateParts[0],2,'0', STR_PAD_LEFT).'-'
        .str_pad($dateParts[1],2,'0', STR_PAD_LEFT);
    }
}