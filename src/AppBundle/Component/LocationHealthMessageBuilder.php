<?php

namespace AppBundle\Component;


use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\LocationHealth;
use AppBundle\Entity\LocationHealthMessage;
use AppBundle\Enumerator\RequestType;
use AppBundle\Util\HealthChecker;

class LocationHealthMessageBuilder
{
    /**
     * @param DeclareArrival|DeclareImport $declareIn
     * @param LocationHealth $previousLocationHealthOfDestination
     * @return LocationHealthMessage
     */
    public static function build($declareIn, $previousLocationHealthOfDestination)
    {
        $animal = $declareIn->getAnimal();
        $location = $declareIn->getLocation();

        $healthMessage = new LocationHealthMessage();
        $healthMessage->setLocation($location);
        $healthMessage->setPreviousLocationHealth($previousLocationHealthOfDestination);
        $healthMessage->setAnimal($animal);
        $healthMessage->setUbnNewOwner($location->getUbn());
        $healthMessage->setUlnCountryCode($declareIn->getUlnCountryCode());
        $healthMessage->setUlnNumber($declareIn->getUlnNumber());

        //Set values related to declare type, DeclareArrival or DeclareImport
        $declareType = Utils::getClassName($declareIn);
        $healthMessage->setReasonOfHealthStatusDemotion(Utils::getClassName($declareIn));

        //By default the healthStatuses are false, like for DeclareImport. But DeclareArrival can override these values.
        $isMaediVisnaStatusHealthy = false;
        $isScrapieStatusHealthy = false;

        switch($declareType) {
            case RequestType::DECLARE_ARRIVAL_ENTITY:
                $healthMessage->setArrival($declareIn);
                $healthMessage->setUbnPreviousOwner($declareIn->getUbnPreviousOwner()); //animalCountryOrigin for arrivals is null.

                $locationHealthOrigin = Utils::returnLastLocationHealth($animal->getLocation()->getHealths());
                $isMaediVisnaStatusHealthy = HealthChecker::verifyIsMaediVisnaStatusHealthy($locationHealthOrigin->getMaediVisnaStatus());
                $isScrapieStatusHealthy = HealthChecker::verifyIsScrapieStatusHealthy($locationHealthOrigin->getScrapieStatus());
                break;

            case RequestType::DECLARE_IMPORT_ENTITY;
                $healthMessage->setImport($declareIn);
                $healthMessage->setAnimalCountryOrigin($declareIn->getAnimalCountryOrigin()); //ubnPreviousOwner for imports is null.

                $isMaediVisnaStatusHealthy = false;
                $isScrapieStatusHealthy = false;
                break;

            default:
                break;
        }

        //Set illness  booleans
        if($isMaediVisnaStatusHealthy) {
            $healthMessage->setCheckForMaediVisna(false);
        } else {
            $healthMessage->setCheckForMaediVisna(true);
        }

        if($isScrapieStatusHealthy) {
            $healthMessage->setCheckForScrapie(false);
        } else {
            $healthMessage->setCheckForScrapie(true);
        }

        return $healthMessage;
    }
}