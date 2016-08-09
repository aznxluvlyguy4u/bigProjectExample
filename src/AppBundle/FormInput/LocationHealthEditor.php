<?php

namespace AppBundle\FormInput;

use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use AppBundle\Entity\MaediVisna;
use AppBundle\Entity\Scrapie;
use AppBundle\Enumerator\MaediVisnaStatus;
use AppBundle\Enumerator\ScrapieStatus;
use AppBundle\Util\Finder;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class LocationHealthEditor
 * @package AppBundle\FormInput
 */
class LocationHealthEditor
{
    const defaultMaediVisnaStatus = MaediVisnaStatus::UNDER_OBSERVATION;
    const defaultScrapieStatus = ScrapieStatus::UNDER_OBSERVATION;

    /**
     * @param EntityManager $em
     * @param ArrayCollection $content
     * @param Location $location
     * @return Location
     */
    public static function edit(EntityManager $em, Location $location, ArrayCollection $content)
    {
        /* Initialize LocationHealth with blank values and no illnesses if null */
        if($location->getLocationHealth() == null) {
            $locationHealth = new LocationHealth();
            $location->setLocationHealth($locationHealth);
            $locationHealth->setLocation($location);
            $em->persist($location);
            $em->persist($locationHealth);
            $em->flush();
        }
        /** @var LocationHealth $locationHealth */
        $locationHealth = $location->getLocationHealth();

        //Get the latest locationHealth status to use as a benchmark
        $latestIllnessStatuses = Finder::findLatestActiveIllnessStatusesOfLocation($location, $em);

        /* Scrapie values */
        
        $lastScrapieStatus = $latestIllnessStatuses->get(JsonInputConstant::SCRAPIE_STATUS);
        $lastScrapieStatus = Utils::fillNullOrEmptyString($lastScrapieStatus, self::defaultScrapieStatus);
        $lastScrapieEndDate = $latestIllnessStatuses->get(JsonInputConstant::SCRAPIE_END_DATE);
        $lastScrapieCheckDate = $latestIllnessStatuses->get(JsonInputConstant::SCRAPIE_CHECK_DATE);
        
        $newScrapieStatus = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::SCRAPIE_STATUS, $content);
        $newScrapieCheckDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::SCRAPIE_CHECK_DATE, $content);
        $newScrapieEndDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::SCRAPIE_END_DATE, $content);

        $scrapieStatusChanged = $newScrapieStatus != $lastScrapieStatus;
        $scrapieDatesChanged = !($newScrapieCheckDate == $lastScrapieCheckDate && $newScrapieEndDate == $lastScrapieEndDate);

        /* MaediVisna values */

        $lastMaediVisnaStatus = $latestIllnessStatuses->get(JsonInputConstant::MAEDI_VISNA_STATUS);
        $lastMaediVisnaStatus = Utils::fillNullOrEmptyString($lastMaediVisnaStatus, self::defaultMaediVisnaStatus);
        $lastMaediVisnaEndDate = $latestIllnessStatuses->get(JsonInputConstant::MAEDI_VISNA_END_DATE);
        $lastMaediVisnaCheckDate = $latestIllnessStatuses->get(JsonInputConstant::MAEDI_VISNA_CHECK_DATE);
        
        $newMaediVisnaStatus = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::MAEDI_VISNA_STATUS, $content);
        $newMaediVisnaCheckDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::MAEDI_VISNA_CHECK_DATE, $content);
        $newMaediVisnaEndDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::MAEDI_VISNA_END_DATE, $content);

        $maediVisnaStatusChanged = $newMaediVisnaStatus != $lastMaediVisnaStatus;
        $maediVisnaDatesChanged = !($newMaediVisnaCheckDate == $lastMaediVisnaCheckDate && $newMaediVisnaEndDate == $lastMaediVisnaEndDate);

        /* LocationHealth Entity */

        //Only create a new Scrapie if there was any change in the values
        if($scrapieStatusChanged || $scrapieDatesChanged) {
            $scrapie = new Scrapie($newScrapieStatus, $newScrapieEndDate);
            $scrapie->setCheckDate($newScrapieCheckDate);
            $locationHealth->addScrapie($scrapie);
            $scrapie->setLocationHealth($locationHealth);
            $em->persist($scrapie);
        }

        //Only create a new MaediVisna if there was any change in the values
        if($maediVisnaStatusChanged || $maediVisnaDatesChanged) {
            $maediVisna = new MaediVisna($newMaediVisnaStatus, $newMaediVisnaEndDate);
            $maediVisna->setCheckDate($newMaediVisnaCheckDate);
            $locationHealth->addMaediVisna($maediVisna);
            $maediVisna->setLocationHealth($locationHealth);
            $em->persist($maediVisna);
        }

        //Persist LocationHealth AND FLUSH
        if($maediVisnaStatusChanged) { $locationHealth->setCurrentMaediVisnaStatus($newMaediVisnaStatus); }
        if($scrapieStatusChanged) { $locationHealth->setCurrentScrapieStatus($newScrapieStatus); }

        if($maediVisnaStatusChanged || $maediVisnaDatesChanged || $scrapieStatusChanged || $scrapieDatesChanged) {
            $em->persist($locationHealth);
            $em->flush();
        }


        return $location;
    }
}