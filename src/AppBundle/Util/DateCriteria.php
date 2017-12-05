<?php


namespace AppBundle\Util;


use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\Common\Collections\Expr\Comparison;


/**
 * Class DateCriteria
 *
 * Compare dates without time precision.
 *
 * @ORM\Entity(repositoryClass="AppBundle\Util")
 * @package AppBundle\Util
 */
class DateCriteria
{
    const ZERO_TIME = "00:00:00";
    const DATE_FORMAT = "Y-m-d";

    /**
     * Return comparison for
     * Dates greater than given date.
     *
     * @param string $field
     * @param \DateTimeInterface $dateTime
     * @return Comparison
     */
    public static function gt($field, \DateTimeInterface $dateTime)
    {
        $date = new \DateTime($dateTime->format(self::DATE_FORMAT).self::ZERO_TIME);
        $date->add(new \DateInterval('P1D')); // Add one day
        return Criteria::expr()->gte($field, $date);
    }


    /**
     * Return comparison for
     * Dates greater than or equal to given date.
     *
     * @param string $field
     * @param \DateTimeInterface $dateTime
     * @return Comparison
     */
    public static function gte($field, \DateTimeInterface $dateTime)
    {
        $date = new \DateTime($dateTime->format(self::DATE_FORMAT).self::ZERO_TIME);
        return Criteria::expr()->gte($field, $date);
    }



    /**
     * Return comparison for
     * Dates smaller than given date.
     *
     * @param string $field
     * @param \DateTimeInterface $dateTime
     * @return Comparison
     */
    public static function lt($field, \DateTimeInterface $dateTime)
    {
        $date = new \DateTime($dateTime->format(self::DATE_FORMAT).self::ZERO_TIME);
        return Criteria::expr()->lt($field, $date);
    }


    /**
     * Return comparison for
     * Dates smaller than or equal to given date.
     *
     * @param string $field
     * @param \DateTimeInterface $dateTime
     * @return Comparison
     */
    public static function lte($field, \DateTimeInterface $dateTime)
    {
        $date = new \DateTime($dateTime->format(self::DATE_FORMAT).self::ZERO_TIME);
        $date->add(new \DateInterval('P1D')); // Add one day
        return Criteria::expr()->lt($field, $date);
    }
}