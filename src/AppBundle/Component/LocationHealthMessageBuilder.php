<?php

namespace AppBundle\Component;


use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\LocationHealth;
use AppBundle\Entity\LocationHealthMessage;
use AppBundle\Enumerator\LocationHealthStatus;
use AppBundle\Enumerator\RequestType;
use AppBundle\Util\HealthChecker;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

class LocationHealthMessageBuilder
{
    /**
     * @param ObjectManager $em
     * @param DeclareArrival|DeclareImport $declareIn
     * @param ArrayCollection $illnesses
     * @param LocationHealth $locationHealthDestination
     * @param LocationHealth|null $locationHealthOrigin
     * @return LocationHealthMessage
     */
    public static function build(ObjectManager $em, $declareIn, ArrayCollection $illnesses, LocationHealth $locationHealthDestination, LocationHealth $locationHealthOrigin = null)
    {
        $location = $declareIn->getLocation();

        $healthMessage = new LocationHealthMessage();
        $healthMessage->setLocation($location);
        $healthMessage->setUbnNewOwner($location->getUbn());
        $healthMessage->setUlnCountryCode($declareIn->getUlnCountryCode());
        $healthMessage->setUlnNumber($declareIn->getUlnNumber());

        //Set Illnesses
        $healthMessage->setMaediVisna($illnesses->get(Constant::MAEDI_VISNA));
        $healthMessage->setScrapie($illnesses->get(Constant::SCRAPIE));

        //Set values related to declare type, DeclareArrival or DeclareImport
        $declareType = Utils::getClassName($declareIn);
        $healthMessage->setReasonOfHealthStatusDemotion(Utils::getClassName($declareIn));

        //Destination
        $maediVisnaStatusDestination = $locationHealthDestination->getCurrentMaediVisnaStatus();
        $scrapieStatusDestination = $locationHealthDestination->getCurrentScrapieStatus();
        $healthMessage->setDestinationMaediVisnaStatus($maediVisnaStatusDestination);
        $healthMessage->setDestinationScrapieStatus($scrapieStatusDestination);

        //Origin
        if($locationHealthOrigin == null) {
            $maediVisnaStatusOrigin = LocationHealthStatus::UNKNOWN;
            $scrapieStatusOrigin = LocationHealthStatus::UNKNOWN;
        } else {
            $maediVisnaStatusOrigin = $locationHealthOrigin->getCurrentMaediVisnaStatus();
            $scrapieStatusOrigin = $locationHealthOrigin->getCurrentScrapieStatus();
        }
        $healthMessage->setOriginMaediVisnaStatus($maediVisnaStatusOrigin);
        $healthMessage->setOriginScrapieStatus($scrapieStatusOrigin);

        $isMaediVisnaStatusOriginHealthy = HealthChecker::verifyIsMaediVisnaStatusHealthy($maediVisnaStatusOrigin);
        $isScrapieStatusOriginHealthy = HealthChecker::verifyIsScrapieStatusHealthy($scrapieStatusOrigin);


        switch($declareType) {
            case RequestType::DECLARE_ARRIVAL_ENTITY:
                $healthMessage->setArrival($declareIn);
                $ubnPreviousOwner = $declareIn->getUbnPreviousOwner();
                $healthMessage->setUbnPreviousOwner($ubnPreviousOwner); //animalCountryOrigin for arrivals is null.
                $healthMessage->setArrivalDate($declareIn->getArrivalDate());
                break;

            case RequestType::DECLARE_IMPORT_ENTITY;
                $healthMessage->setImport($declareIn);
                $healthMessage->setAnimalCountryOrigin($declareIn->getAnimalCountryOrigin()); //ubnPreviousOwner for imports is null.
                $healthMessage->setArrivalDate($declareIn->getImportDate());
                break;

            default:
                break;
        }

        //Set illness  booleans
        if($isMaediVisnaStatusOriginHealthy) {
            $healthMessage->setCheckForMaediVisna(false);
        } else {
            $healthMessage->setCheckForMaediVisna(true);
        }

        if($isScrapieStatusOriginHealthy) {
            $healthMessage->setCheckForScrapie(false);
        } else {
            $healthMessage->setCheckForScrapie(true);
        }

        return $healthMessage;
    }
}