<?php

namespace AppBundle\Component;


use AppBundle\Entity\Client;
use AppBundle\Enumerator\AnimalTransferStatus;
use AppBundle\Enumerator\LiveStockType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\TagStateType;
use Doctrine\Common\Collections\ArrayCollection;

class Count
{
    public static function getErrorCountDeclarations(Client $client)
    {
        $errorCounts = new ArrayCollection();
        $errorCounts->set(RequestType::DECLARE_ARRIVAL, Count::getErrorCountArrivalsAndImports($client));
        $errorCounts->set(RequestType::DECLARE_DEPART, Count::getErrorCountDepartsAndExports($client));
        $errorCounts->set(RequestType::DECLARE_LOSS, Count::getErrorCountLosses($client));
        $errorCounts->set(RequestType::DECLARE_BIRTH, Count::getErrorCountBirths($client));

        return $errorCounts;
    }


    public static function getErrorCountArrivalsAndImports(Client $client)
    {
        return self::getErrorCountArrivals($client) + self::getErrorCountImports($client);
    }

    public static function getErrorCountDepartsAndExports(Client $client)
    {
        return self::getErrorCountDeparts($client) + self::getErrorCountExports($client);
    }

    public static function getErrorCountArrivals(Client $client)
    {
        $count = 0;

        foreach($client->getCompanies() as $company){
            foreach($company->getLocations() as $location){
                foreach($location->getArrivals() as $arrival){
                    if(self::countAsErrorResponse($arrival)) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    public static function getErrorCountImports(Client $client)
    {
        $count = 0;

        foreach($client->getCompanies() as $company){
            foreach($company->getLocations() as $location){
                foreach($location->getImports() as $arrival){
                    if(self::countAsErrorResponse($arrival)) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    public static function getErrorCountDeparts(Client $client)
    {
        $count = 0;

        foreach($client->getCompanies() as $company){
            foreach($company->getLocations() as $location){
                foreach($location->getDepartures() as $arrival){
                    if(self::countAsErrorResponse($arrival)) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    public static function getErrorCountExports(Client $client)
    {
        $count = 0;

        foreach($client->getCompanies() as $company){
            foreach($company->getLocations() as $location){
                foreach($location->getExports() as $arrival){
                    if(self::countAsErrorResponse($arrival)) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    public static function getErrorCountLosses(Client $client)
    {
        $count = 0;

        foreach($client->getCompanies() as $company){
            foreach($company->getLocations() as $location){
                foreach($location->getLosses() as $arrival){
                    if(self::countAsErrorResponse($arrival)) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    public static function getErrorCountBirths(Client $client)
    {
        $count = 0;

        foreach($client->getCompanies() as $company){
            foreach($company->getLocations() as $location){
                foreach($location->getBirths() as $arrival){
                    if(self::countAsErrorResponse($arrival)) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    public static function getUnassignedTagsCount(Client $client)
    {
        $count = 0;

        foreach($client->getTags() as $tag){
            if($tag->getTagStatus() == TagStateType::UNASSIGNED) {
                $count++;
            }
        }

        return $count;
    }

    private static function countAsErrorResponse($declaration)
    {
        if($declaration->getRequestState() == RequestStateType::FAILED) {

            $responses = $declaration->getResponses();
            $lastResponse = $responses[sizeof($responses)-1];

            if($lastResponse->getIsRemovedByUser() == false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return an ArrayCollection with keys:
     * - pedigree
     * - non-pedigree
     * - total
     * having an integer value for the amount of animals in that category.
     *
     * @param Client $client
     * @return ArrayCollection
     */
    public static function getLiveStockCount(Client $client)
    {

        //Settings
        $isAlive = true;
        $isDepartedOption = false;
        $isExportedOption = false;
        $countTransferring = false;

        if($countTransferring) {
            $transferState = AnimalTransferStatus::TRANSFERRING;
        } else {
            $transferState = AnimalTransferStatus::NULL;
        }

        //Initialize counters
        $pedigreeAdults = 0;
        $pedigreeLambs = 0;
        $nonPedigreeAdults = 0;
        $nonPedigreeLambs = 0;

        $adultDateOfBirthLimit = Utils::getAdultDateOfBirthLimit();

        foreach($client->getCompanies() as $company) {
            foreach($company->getLocations() as $location) {
                foreach($location->getAnimals() as $animal) {

                    $isOwnedAnimal = $animal->getIsAlive() == $isAlive
                        && $animal->getIsExportAnimal() == $isExportedOption
                        && $animal->getIsDepartedAnimal() == $isDepartedOption
                        && ($animal->getTransferState() == AnimalTransferStatus::NULL
                            || $animal->getTransferState() == $transferState);

                    $isPedigree = $animal->getPedigreeCountryCode() != null
                        && $animal->getPedigreeNumber() != null;

                    $dateOfBirth = $animal->getDateOfBirth();

                    if($isOwnedAnimal) {
                        if($isPedigree) {
                            if($dateOfBirth > $adultDateOfBirthLimit) { // is under 1 years old
                                $pedigreeLambs++;
                            } else { // is adult
                                $pedigreeAdults++;
                            }

                        } else { //is non-pedigree
                            if($dateOfBirth > $adultDateOfBirthLimit) { // is under 1 years old
                                $nonPedigreeLambs++;
                            } else { // is adult
                                $nonPedigreeAdults++;
                            }
                        }
                    }

                }
            }
        }

        $pedigreeTotal = $pedigreeAdults + $pedigreeLambs;
        $nonPedigreeTotal = $nonPedigreeAdults + $nonPedigreeLambs;

        $count = new ArrayCollection();
        $count->set(LiveStockType::PEDIGREE_ADULT, $pedigreeAdults);
        $count->set(LiveStockType::PEDIGREE_LAMB, $pedigreeLambs);
        $count->set(LiveStockType::PEDIGREE_TOTAL, $pedigreeTotal);
        $count->set(LiveStockType::NON_PEDIGREE_ADULT, $nonPedigreeAdults);
        $count->set(LiveStockType::NON_PEDIGREE_LAMB, $nonPedigreeLambs);
        $count->set(LiveStockType::NON_PEDIGREE_TOTAL, $nonPedigreeTotal);
        $count->set(LiveStockType::ADULT, $nonPedigreeAdults + $pedigreeAdults);
        $count->set(LiveStockType::LAMB, $nonPedigreeLambs + $pedigreeLambs);
        $count->set(LiveStockType::TOTAL, $nonPedigreeTotal + $pedigreeTotal);

        return $count;
    }
}