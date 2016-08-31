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
        $dayOfDateTime = self::getDayOfDateTime($dateTime);
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
     * @param Animal $animal
     * @return int
     */
    public static function ageInSystem($animal)
    {
        if($animal == null) { return null; }
        $dateOfBirth = $animal->getDateOfBirth();
        if($dateOfBirth == null) { return null; }

        $isAnimalInNsfoSystem = $animal->getLocation() != null;


        if($isAnimalInNsfoSystem) {
            return TimeUtil::getAgeYear($dateOfBirth, $animal->getDateOfDeath());

        } else {
            $lastResidence = $animal->getAnimalResidenceHistory()->last();
            /** @var AnimalResidence $lastResidence */
            if($lastResidence != null) {
                $lastDate = $lastResidence->getEndDate();
                if($lastDate != null) {
                    $lastDate = $lastResidence->getStartDate();
                }

                if($lastDate != null) {
                    return TimeUtil::getAgeYear($dateOfBirth, $lastDate);
                }
            }
            return null;
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
        $oneYearOldLimit = $dateOfBirth;
        $oneYearOldLimit->add(new \DateInterval('P' . $months . "M"));

        if($oneYearOldLimit < $earliestLitterDate) {
            return false;
        } else {
            return true;
        }
    }
}