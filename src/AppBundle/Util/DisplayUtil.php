<?php


namespace AppBundle\Util;


use AppBundle\Enumerator\GenderType;

class DisplayUtil
{
    const EMPTY_PRODUCTION = '-/-/-/-';

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
                $oneYearMark = '*';
            }
        }

        $ageInTheNsfoSystem = TimeUtil::ageInSystemForProductionValue($dateOfBirth, $latestLitterDate);
        if($ageInTheNsfoSystem == null) {
            $ageInTheNsfoSystem = '-';
        }

        return $ageInTheNsfoSystem.'/'.$litterCount.'/'.$totalBornCount.'/'.$bornAliveCount.$oneYearMark;
    }

    
}