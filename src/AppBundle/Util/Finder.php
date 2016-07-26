<?php

namespace AppBundle\Util;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealth;
use AppBundle\Entity\LocationHealthMessage;
use AppBundle\Entity\Scrapie;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

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
    public static function findLatestActiveIllnessesOfLocation(Location $location, ObjectManager $em)
    {
        $locationHealth = $location->getLocationHealth();

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('locationHealth', $locationHealth))
            ->andWhere(Criteria::expr()->eq('isHidden', false))
            ->orderBy(['checkDate' => Criteria::DESC])
            ->setMaxResults(1);

        $lastMaediVisnaResults = $em->getRepository('AppBundle:MaediVisna')
            ->matching($criteria);

        if($lastMaediVisnaResults->count() > 0) {
            $lastMaediVisna = $lastMaediVisnaResults->get(0);
        } else {
            $lastMaediVisna = null;
        }

        $lastScrapieResults = $em->getRepository('AppBundle:Scrapie')
            ->matching($criteria);

        if($lastScrapieResults->count() > 0) {
            $lastScrapie = $lastScrapieResults->get(0);
        } else {
            $lastScrapie = null;
        }

        $illnesses = new ArrayCollection();
        $illnesses->set(Constant::SCRAPIE, $lastScrapie);
        $illnesses->set(Constant::MAEDI_VISNA, $lastMaediVisna);

        return $illnesses;
    }


    public static function getMaediVisnas(Location $location, ObjectManager $em)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('locationHealth', $location->getLocationHealth()))
            ->andWhere(Criteria::expr()->eq('isHidden', false))
            ->orderBy(['checkDate' => Criteria::ASC]);

        return $em->getRepository('AppBundle:MaediVisna')
            ->matching($criteria);
    }

    public static function getScrapies(Location $location, ObjectManager $em)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('locationHealth', $location->getLocationHealth()))
            ->andWhere(Criteria::expr()->eq('isHidden', false))
            ->orderBy(['checkDate' => Criteria::ASC]);

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