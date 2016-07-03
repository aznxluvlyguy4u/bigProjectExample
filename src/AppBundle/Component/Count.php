<?php

namespace AppBundle\Component;


use AppBundle\Entity\Client;
use AppBundle\Entity\DeclarationDetail;
use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\Location;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\RetrieveCountries;
use AppBundle\Entity\RetrieveTags;
use AppBundle\Entity\RetrieveAnimalDetails;
use AppBundle\Entity\RetrieveUbnDetails;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Enumerator\AnimalTransferStatus;
use AppBundle\Enumerator\LiveStockType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\TagStateType;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * Class Count
 * @package AppBundle\Component
 */
class Count
{
    /**
     * @param Client $client
     * @return ArrayCollection
     */
    public static function getErrorCountDeclarations(Client $client)
    {
        $errorCounts = new ArrayCollection();
        $errorCounts->set(RequestType::DECLARE_ARRIVAL, Count::getErrorCountArrivalsAndImports($client));
        $errorCounts->set(RequestType::DECLARE_DEPART, Count::getErrorCountDepartsAndExports($client));
        $errorCounts->set(RequestType::DECLARE_LOSS, Count::getErrorCountLosses($client));
        $errorCounts->set(RequestType::DECLARE_BIRTH, Count::getErrorCountBirths($client));

        return $errorCounts;
    }

    /**
     * @param Location $location
     * @return ArrayCollection
     */
    public static function getErrorCountDeclarationsPerLocation(Location $location)
    {
        $errorCounts = new ArrayCollection();
        $errorCounts->set(RequestType::DECLARE_ARRIVAL, Count::getErrorCountArrivalsAndImportsPerLocation($location));
        $errorCounts->set(RequestType::DECLARE_DEPART, Count::getErrorCountDepartsAndExportsPerLocation($location));
        $errorCounts->set(RequestType::DECLARE_LOSS, Count::getErrorCountLossesLocation($location));
        $errorCounts->set(RequestType::DECLARE_BIRTH, Count::getErrorCountBirthsLocation($location));

        return $errorCounts;
    }

    /**
     * @param Client $client
     * @return int
     */
    public static function getErrorCountArrivalsAndImports(Client $client)
    {
        return self::getErrorCountArrivals($client) + self::getErrorCountImports($client);
    }

    /**
     * @param Location $location
     * @return int
     */
    public static function getErrorCountArrivalsAndImportsPerLocation(Location $location)
    {
        return self::getErrorCountArrivalsLocation($location) + self::getErrorCountImportsLocation($location);
    }

    /**
     * @param Client $client
     * @return int
     */
    public static function getErrorCountDepartsAndExports(Client $client)
    {
        return self::getErrorCountDeparts($client) + self::getErrorCountExports($client);
    }

    /**
     * @param Location $location
     * @return int
     */
    public static function getErrorCountDepartsAndExportsPerLocation(Location $location)
    {
        return self::getErrorCountDepartsLocation($location) + self::getErrorCountExportsLocation($location);
    }


    /**
     * @param Client $client
     * @return int
     */
    public static function getErrorCountArrivals(Client $client)
    {
        $count = 0;

        foreach($client->getCompanies() as $company){
            foreach($company->getLocations() as $location){
                self::getErrorCountArrivalsLocation($location);
            }
        }

        return $count;
    }

    /**
     * @param Location $location
     * @return int
     */
    public static function getErrorCountArrivalsLocation(Location $location)
    {
        $count = 0;

        foreach($location->getArrivals() as $arrival){
            if(self::countAsErrorResponse($arrival)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param Client $client
     * @return int
     */
    public static function getErrorCountImports(Client $client)
    {
        $count = 0;

        foreach($client->getCompanies() as $company){
            foreach($company->getLocations() as $location){
                self::getErrorCountImportsLocation($location);
            }
        }

        return $count;
    }

    /**
     * @param Location $location
     * @return int
     */
    public static function getErrorCountImportsLocation(Location $location)
    {
        $count = 0;

        foreach($location->getImports() as $import){
            if(self::countAsErrorResponse($import)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param Client $client
     * @return int
     */
    public static function getErrorCountDeparts(Client $client)
    {
        $count = 0;

        foreach($client->getCompanies() as $company){
            foreach($company->getLocations() as $location){
                self::getErrorCountDepartsLocation($location);
            }
        }

        return $count;
    }

    /**
     * @param Location $location
     * @return int
     */
    public static function getErrorCountDepartsLocation(Location $location)
    {
        $count = 0;

        foreach($location->getDepartures() as $departure){
            if(self::countAsErrorResponse($departure)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param Client $client
     * @return int
     */
    public static function getErrorCountExports(Client $client)
    {
        $count = 0;

        foreach($client->getCompanies() as $company){
            foreach($company->getLocations() as $location){
                self::getErrorCountExportsLocation($location);
            }
        }

        return $count;
    }

    /**
     * @param Location $location
     * @return int
     */
    public static function getErrorCountExportsLocation(Location $location)
    {
        $count = 0;

        foreach($location->getExports() as $export){
            if(self::countAsErrorResponse($export)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param Client $client
     * @return int
     */
    public static function getErrorCountLosses(Client $client)
    {
        $count = 0;

        foreach($client->getCompanies() as $company){
            foreach($company->getLocations() as $location){
                self::getErrorCountLossesLocation($location);
            }
        }

        return $count;
    }

    /**
     * @param Location $location
     * @return int
     */
    public static function getErrorCountLossesLocation(Location $location)
    {
        $count = 0;

        foreach($location->getLosses() as $loss){
            if(self::countAsErrorResponse($loss)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param Client $client
     * @return int
     */
    public static function getErrorCountBirths(Client $client)
    {
        $count = 0;

        foreach($client->getCompanies() as $company){
            foreach($company->getLocations() as $location){
                self::getErrorCountBirthsLocation($location);
            }
        }

        return $count;
    }

    /**
     * @param Location $location
     * @return int
     */
    public static function getErrorCountBirthsLocation(Location $location)
    {
        $count = 0;

        foreach($location->getBirths() as $birth){
            if(self::countAsErrorResponse($birth)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param Client $client
     * @return int
     */
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

    /**
     * @param DeclareArrival|DeclareImport|DeclareExport|DeclareDepart|DeclareBirth|DeclareLoss|DeclareAnimalFlag|DeclarationDetail|DeclareTagsTransfer|RetrieveTags|RevokeDeclaration|RetrieveAnimals|RetrieveAnimals|RetrieveCountries|RetrieveUBNDetails|RetrieveAnimalDetails $declaration
     * @return bool
     */
    private static function countAsErrorResponse($declaration)
    {
        if($declaration->getRequestState() == RequestStateType::FAILED) {

            $responses = $declaration->getResponses();

            if(sizeof($responses) > 0) {
                $lastResponse = $responses[sizeof($responses)-1];

                if($lastResponse->getIsRemovedByUser() == false) {
                    return true;
                }
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
     * @param Location $location
     * @return ArrayCollection
     */
    public static function getLiveStockCountLocation(Location $location)
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