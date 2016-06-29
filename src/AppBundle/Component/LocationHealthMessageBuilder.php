<?php

namespace AppBundle\Component;


use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\LocationHealth;
use AppBundle\Entity\LocationHealthMessage;
use AppBundle\Enumerator\RequestType;
use AppBundle\Util\HealthChecker;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

class LocationHealthMessageBuilder
{
    /**
     * @param DeclareArrival|DeclareImport $declareIn
     * @param integer $idPreviousLocationHealthOfDestination
     * @param Animal $animal
     * @return LocationHealthMessage
     */
    public static function build(ObjectManager $em, $declareIn, $idPreviousLocationHealthOfDestination = null, Animal $animal = null)
    {
        if($animal == null) {
            $animal = $declareIn->getAnimal();
        }

        $location = $declareIn->getLocation();

        $healthMessage = new LocationHealthMessage();
        $healthMessage->setLocation($location);
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
                $ubnPreviousOwner = $declareIn->getUbnPreviousOwner();
                $healthMessage->setUbnPreviousOwner($ubnPreviousOwner); //animalCountryOrigin for arrivals is null.

                $locationOrigin = $em->getRepository(Constant::LOCATION_REPOSITORY)->findByUbn($ubnPreviousOwner);
                if($locationOrigin != null) {
                    $locationHealthOrigin = Utils::returnLastLocationHealth($locationOrigin->getHealths());
                    $isMaediVisnaStatusHealthy = HealthChecker::verifyIsMaediVisnaStatusHealthy($locationHealthOrigin->getMaediVisnaStatus());
                    $isScrapieStatusHealthy = HealthChecker::verifyIsScrapieStatusHealthy($locationHealthOrigin->getScrapieStatus());
                } else {
                    $isMaediVisnaStatusHealthy = false;
                    $isScrapieStatusHealthy = false;
                }
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
        //id of previousLocationHealth
        if($idPreviousLocationHealthOfDestination != null) {
            $healthMessage->setPreviousLocationHealthId($idPreviousLocationHealthOfDestination);
        }

        return $healthMessage;
    }
}