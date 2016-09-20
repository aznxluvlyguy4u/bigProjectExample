<?php

namespace AppBundle\Util;

use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use AppBundle\Entity\LocationHealthMessage;
use AppBundle\Entity\MaediVisna;
use AppBundle\Entity\Scrapie;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;

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
     * @param Location $location
     * @return ArrayCollection
     */
    public static function findLatestActiveIllnessStatusesOfLocation(Location $location, ObjectManager $em)
    {
        $results = new ArrayCollection();

        $lastMaediVisna = self::findLatestActiveMaediVisna($location, $em);

        if($lastMaediVisna == null) {
            $results->set(JsonInputConstant::MAEDI_VISNA_STATUS, null);
            $results->set(JsonInputConstant::MAEDI_VISNA_CHECK_DATE, null);
            $results->set(JsonInputConstant::MAEDI_VISNA_END_DATE, null);
        } else {
            $results->set(JsonInputConstant::MAEDI_VISNA_STATUS, $lastMaediVisna->getStatus());
            $results->set(JsonInputConstant::MAEDI_VISNA_CHECK_DATE, $lastMaediVisna->getCheckDate());
            $results->set(JsonInputConstant::MAEDI_VISNA_END_DATE, $lastMaediVisna->getEndDate());
        }
        
        $lastScrapie = self::findLatestActiveScrapie($location, $em);

        if($lastScrapie == null) {
            $results->set(JsonInputConstant::SCRAPIE_STATUS, null);
            $results->set(JsonInputConstant::SCRAPIE_CHECK_DATE, null);
            $results->set(JsonInputConstant::SCRAPIE_END_DATE, null);
        } else {
            $results->set(JsonInputConstant::SCRAPIE_STATUS, $lastScrapie->getStatus());
            $results->set(JsonInputConstant::SCRAPIE_CHECK_DATE, $lastScrapie->getCheckDate());
            $results->set(JsonInputConstant::SCRAPIE_END_DATE, $lastScrapie->getEndDate());
        }

        return $results;
    }


    /**
     * @param Location $location
     * @return ArrayCollection
     */
    public static function findLatestActiveIllnessesOfLocation(Location $location, ObjectManager $em)
    {
        $illnesses = new ArrayCollection();
        $illnesses->set(Constant::SCRAPIE, self::findLatestActiveScrapie($location, $em));
        $illnesses->set(Constant::MAEDI_VISNA, self::findLatestActiveMaediVisna($location, $em));

        return $illnesses;
    }


    /**
     * @param Location $location
     * @param ObjectManager $em
     * @return MaediVisna|null
     */
    public static function findLatestActiveMaediVisna(Location $location, ObjectManager $em)
    {
        $locationHealth = $location->getLocationHealth();

        if($locationHealth == null) {
            return null;
        }

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('locationHealth', $locationHealth))
            ->andWhere(Criteria::expr()->eq('isHidden', false))
            ->orderBy(['checkDate' => Criteria::DESC, 'logDate' => Criteria::DESC])
            ->setMaxResults(1);

        $lastMaediVisnaResults = $em->getRepository('AppBundle:MaediVisna')
            ->matching($criteria);

        if($lastMaediVisnaResults->count() > 0) {
            $lastMaediVisna = $lastMaediVisnaResults->get(0);
        } else {
            $lastMaediVisna = null;
        }

        return $lastMaediVisna;
    }


    /**
     * @param Location $location
     * @param ObjectManager $em
     * @return Scrapie|null
     */
    public static function findLatestActiveScrapie(Location $location, ObjectManager $em)
    {
        $locationHealth = $location->getLocationHealth();

        if($locationHealth == null) {
            return null;
        }

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('locationHealth', $locationHealth))
            ->andWhere(Criteria::expr()->eq('isHidden', false))
            ->orderBy(['checkDate' => Criteria::DESC, 'logDate' => Criteria::DESC])
            ->setMaxResults(1);

        $lastScrapieResults = $em->getRepository('AppBundle:Scrapie')
            ->matching($criteria);

        if($lastScrapieResults->count() > 0) {
            $lastScrapie = $lastScrapieResults->get(0);
        } else {
            $lastScrapie = null;
        }

        return $lastScrapie;
    }


    public static function getMaediVisnas(Location $location, ObjectManager $em)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('locationHealth', $location->getLocationHealth()))
            ->andWhere(Criteria::expr()->eq('isHidden', false))
            ->orderBy(['checkDate' => Criteria::ASC, 'logDate' => Criteria::ASC]);

        return $em->getRepository('AppBundle:MaediVisna')
            ->matching($criteria);
    }

    public static function getScrapies(Location $location, ObjectManager $em)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('locationHealth', $location->getLocationHealth()))
            ->andWhere(Criteria::expr()->eq('isHidden', false))
            ->orderBy(['checkDate' => Criteria::ASC, 'logDate' => Criteria::ASC]);

        return $em->getRepository('AppBundle:Scrapie')
            ->matching($criteria);
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