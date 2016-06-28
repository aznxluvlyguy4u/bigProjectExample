<?php

namespace AppBundle\Util;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealthMessage;
use AppBundle\Entity\Scrapie;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * This class contains methods to find objects or values in given
 * arrays, Collections or ArrayCollections.
 *
 * Class Finder
 * @package AppBundle\Util
 */
class Finder
{
    /**
     * @param array $animals
     * @param string $ulnCountryCode
     * @param string $ulnNumber
     * @return Animal
     */
    public static function findAnimalByUlnValues($animals, $ulnCountryCode, $ulnNumber)
    {
        foreach($animals as $animal)
        {
            //Nested ifs are used to minimize the necessary operations
            //for the vast majority of negative matches
            if($animal->getUlnCountryCode() == $ulnCountryCode){
                if($animal->getUlnNumber() == $ulnNumber){
                    return $animal;
                }
            }
        }

        return null;
    }

    /**
     * @param DeclareArrival|DeclareImport $declareIn
     * @return int|null
     */
    public static function findLocationHealthMessageArrayKey($declareIn)
    {
        //returned in ascending order, ordered by arrivalDate/importDate
        $locationHealthMessages = $declareIn->getLocation()->getHealthMessages();

        $messageCount = $locationHealthMessages->count();
        $requestId = $declareIn->getRequestId();

        if ($messageCount == 0) {
            return null;

        } else {
            //Loop backwards to start from the most recent arrival/import
            for ($i = $messageCount-1; $i >= 0; $i--) {
                $locationHealthMessage = $locationHealthMessages->get($i);

                if($requestId == $locationHealthMessage->getRequestId()) {
                    return $i;
                }
            }

            return null;
        }
    }

    /**
     * @param Collection $locationHealthMessages returned in ascending order, ordered by arrivalDate/importDate
     * @param int $locationHealthMessageArrayKey
     * @return ArrayCollection|null
     */
    public static function findIllnessesByArrayKey(Collection $locationHealthMessages, $locationHealthMessageArrayKey)
    {
        $locationHealthMessage = $locationHealthMessages->get($locationHealthMessageArrayKey);

        $illnesses = new ArrayCollection();
        $illnesses->set(Constant::MAEDI_VISNA, $locationHealthMessage->getMaediVisna());
        $illnesses->set(Constant::SCRAPIE, $locationHealthMessage->getScrapie());

        return $illnesses;
    }

    /**
     * @param Location $location
     * @return ArrayCollection
     */
    public static function findLatestActiveIllnessesOfLocation(Location $location)
    {
        $locationHealth = $location->getLocationHealth();
        $scrapies = $locationHealth->getScrapies();
        $maediVisnas = $locationHealth->getMaediVisnas();
        $scrapieCount = $scrapies->count();
        $maediVisnaCount = $maediVisnas->count();

        $illnesses = new ArrayCollection();
        
        //Loop backwards to start from the most recent illnesses
                
        for($i = $scrapieCount-1; $i >=0; $i--) {
            $scrapie = $scrapies->get($i);
            if($scrapie->getIsHidden() == false) {
                $illnesses->set(Constant::SCRAPIE, $scrapie);
            }            
        }

        for($i = $maediVisnaCount-1; $i >=0; $i--) {
            $maediVisna = $maediVisnas->get($i);
            if($maediVisna->getIsHidden() == false) {
                $illnesses->set(Constant::MAEDI_VISNA, $maediVisna);
            }
        }

        return $illnesses;
    }

    /**
     * @param Collection $locationHealthMessages returned in ascending order, ordered by arrivalDate/importDate
     * @param int $locationHealthMessageArrayKey
     * @return int|null
     */
    public static function findKeyPreviousNonRevokedLocationHealthMessage(Collection $locationHealthMessages, $locationHealthMessageArrayKey)
    {
        //Loop backwards to start from the most recent arrival/import before the given key
        for ($i = $locationHealthMessageArrayKey-1; $i >= 0; $i--) {
            $locationHealthMessage = $locationHealthMessages->get($i);
            $requestState = $locationHealthMessage->getRequestState();

            if($requestState != RequestStateType::REVOKED) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param Client $client
     * @return ArrayCollection
     */
    public static function findUbnsOfClient(Client $client)
    {
        $ubns = new ArrayCollection();

        $companies = $client->getCompanies();
        if(sizeof($companies) > 0) {
            foreach($companies as $company) {
                $locations = $company->getLocations();
                if(sizeof($locations) > 0) {
                    foreach($locations as $location) {
                        $ubns->add($location->getUbn());
                    }
                }
            }
        }

        return $ubns;
    }

    /**
     * @param Client $client
     * @return ArrayCollection
     */
    public static function findLocationsOfClient(Client $client)
    {
        $locations = new ArrayCollection();

        $companies = $client->getCompanies();
        if(sizeof($companies) > 0) {
            foreach($companies as $company) {
                $locations = $company->getLocations();
            }
        }

        return $locations;
    }
}