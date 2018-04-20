<?php


namespace AppBundle\Util;


class DateUtil
{
    const DATE_STRING_FORMAT_FILENAME = 'Y-m-d_H\hi\ms\s';
    const DEFAULT_SQL_DATE_STRING_FORMAT = 'DD-MM-YYYY';


    /**
     * A practical "End of Time"
     *
     * @return \DateTime
     */
    public static function endOfTime()
    {
        return new \DateTime('3000-01-01');
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
     * @param string $format
     * @return string
     */
    public static function getTimeStamp($format)
    {
        return (new \DateTime())->format($format);
    }

    /** @return string */
    public static function getTimeStampNow() { return self::getTimeStamp('Y-m-d_H:i:s'); }
    /** @return string */
    public static function getTimeStampToday() { return self::getTimeStamp('Y-m-d'); }

    /**
     * @param \DateTime $dateTime
     * @return string
     */
    public static function getTimeStampForFileName($dateTime) {
        return $dateTime instanceof \DateTime ? $dateTime->format(self::DATE_STRING_FORMAT_FILENAME) : self::getTimeStamp(self::DATE_STRING_FORMAT_FILENAME);
    }

    /**
     * @param \DateTime $dateTime
     * @return string
     */
    public static function getMDYYYYDateString($dateTime)
    {
        return $dateTime->format('d-m-Y');
    }


    /**
     * @param string|\DateTime $dateTime
     * @return null|int
     */
    public static function getYearFromDateStringOrDateTime($dateTime)
    {
        if ($dateTime === null) {
            return null;
        }

        if ($dateTime instanceof \DateTime) {
            $date = $dateTime;
        } else {
            $date = new \DateTime($dateTime);
        }

        return intval($date->format('Y'));
    }


    /**
     * @param $dateTime
     * @return \DateTime|null
     */
    public static function getFirstDateOfGivenDateTime($dateTime)
    {
        if (!($dateTime instanceof \DateTime)) {
            return null;
        }

        return new \DateTime($dateTime->format('Y-m-01'));
    }


    /**
     * @param \DateTime $dateTime
     * @param int $months
     * @return \DateTime|null|static
     */
    public static function addMonths(\DateTime $dateTime, $months)
    {
        if (!($dateTime instanceof \DateTime)) {
            return null;
        }

        $newDateTime = clone $dateTime;

        if ($months === 0) {
            return $newDateTime;
        }

        $dateInterval = self::monthsDateInterval($newDateTime, abs($months));

        return $months > 0 ? $newDateTime->add($dateInterval) : $newDateTime->sub($dateInterval);
    }


    /**
     * @param \DateTime $dateTime
     * @param int $months
     * @return bool|\DateInterval|\DateTime
     */
    private static function monthsDateInterval(\DateTime $dateTime, $months)
    {
        if ($months === 0) {
            return $dateTime;
        }

        $next = new \DateTime($dateTime->format('d-m-Y H:i:s'));
        $next->modify('last day of +'.$months.' month');

        if( $dateTime->format('d') > $next->format('d') ) {
            return $dateTime->diff($next);
        } else {
            return new \DateInterval('P'.$months.'M');
        }
    }
}