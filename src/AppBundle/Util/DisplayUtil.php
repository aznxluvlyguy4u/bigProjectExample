<?php


namespace AppBundle\Util;


use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\PredicateType;

class DisplayUtil
{
    const EMPTY_PRODUCTION = '-/-/-/-';
    const MIN_PREDICATE_SCORE_FOR_DISPLAY = 13;
    const ONE_YEAR_MARK = '*';
    const MISSING_AGE_SIGN = '-';

    /**
     *
     * nLing = stillBornCount + bornAliveCount
     * production = (ewe) litters (litter * nLing)

    production: a/b/c/d e
    a: age in years from birth until date of last Litter
    b: litterCount
    c: total number of offspring (stillborn + bornAlive)
    d: total number of bornAliveCount
    e: (*) als een ooi ooit heeft gelammerd tussen een leeftijd van 6 en 18 maanden
     *
     * @param \DateTime $dateOfBirth
     * @param \DateTime $earliestLitterDate
     * @param \DateTime $latestLitterDate
     * @param int $litterCount
     * @param int $totalBornCount
     * @param int $bornAliveCount
     * @param string $gender
     * @return string
     */
    public static function parseProductionString($dateOfBirth, $earliestLitterDate, $latestLitterDate, $litterCount, $totalBornCount, $bornAliveCount, $gender)
    {
        if($gender == GenderType::NEUTER || $gender == GenderType::O || $litterCount == 0) { return self::EMPTY_PRODUCTION; }

        //By default there is no oneYearMark
        $oneYearMark = '';
        if($gender == GenderType::FEMALE || $gender == GenderType::V) {
            if(TimeUtil::isGaveBirthAsOneYearOld($dateOfBirth, $earliestLitterDate)){
                $oneYearMark = self::ONE_YEAR_MARK;
            }
        }

        $ageInTheNsfoSystem = TimeUtil::ageInSystemForProductionValue($dateOfBirth, $latestLitterDate);
        if($ageInTheNsfoSystem == null) {
            $ageInTheNsfoSystem = self::MISSING_AGE_SIGN;
        }

        return $ageInTheNsfoSystem.'/'.$litterCount.'/'.$totalBornCount.'/'.$bornAliveCount.$oneYearMark;
    }


    /**
     * @param $ageInTheNsfoSystem
     * @param $litterCount
     * @param $totalOffspringCount
     * @param $bornAliveOffspringCount
     * @param $addProductionAsterisk
     * @return string
     */
    public static function parseProductionStringFromGivenParts($ageInTheNsfoSystem, $litterCount, $totalOffspringCount, $bornAliveOffspringCount, $addProductionAsterisk)
    {
        if($litterCount == null || $totalOffspringCount == null || $litterCount == 0 || $totalOffspringCount == 0) { return self::EMPTY_PRODUCTION; }

        $oneYearMark = $addProductionAsterisk ? self::ONE_YEAR_MARK : '';
        if($ageInTheNsfoSystem == null || $ageInTheNsfoSystem == 0) {
            $ageInTheNsfoSystem = self::MISSING_AGE_SIGN;
        }

        return $ageInTheNsfoSystem.'/'.$litterCount.'/'.intval($totalOffspringCount).'/'.intval($bornAliveOffspringCount).$oneYearMark;
    }


    /**
     * @param int $litterSize
     * @return string
     */
    public static function parseNLingString($litterSize)
    {
        return $litterSize == null ? '0-ling' : $litterSize.'-ling';
    }


    /**
     * @param string $predicate
     * @param int $predicateScore
     * @return null|string
     */
    public static function parsePredicateString($predicate, $predicateScore)
    {
        if(!array_key_exists($predicate, PredicateType::getAll())) { return null; }
        $score = $predicateScore != null && $predicateScore >= self::MIN_PREDICATE_SCORE_FOR_DISPLAY ? '('.$predicateScore.')' : null;

        return Translation::getAbbreviation($predicate).$score;
    }
}