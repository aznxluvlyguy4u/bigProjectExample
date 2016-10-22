<?php

namespace AppBundle\Component;


use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclarationDetail;
use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareMateRepository;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\Location;
use AppBundle\Entity\Mate;
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
use AppBundle\Enumerator\RequestTypeNonIR;
use AppBundle\Enumerator\TagStateType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraints\DateTime;


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
        $errorCounts->set(RequestTypeNonIR::MATE, Count::getErrorCountMatingsLocation($location));

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
    public static function getErrorCountMatings(Client $client)
    {
        $count = 0;

        foreach($client->getCompanies() as $company){
            foreach($company->getLocations() as $location){
                self::getErrorCountMatingsLocation($location);
            }
        }

        return $count;
    }

    /**
     * @param Location $location
     * @return int
     */
    public static function getErrorCountMatingsLocation(Location $location)
    {
        $count = 0;

        foreach($location->getMatings() as $mate){
            if(self::countNsfoAsErrorResponse($mate)) {
                $count++;
            }
        }

        return $count;
    }
    

    /**
     * @param ObjectManager $em
     * @param int $clientId
     * @return int
     */
    public static function getUnassignedTagsCount(ObjectManager $em, $clientId)
    {
        if(!is_int($clientId)) { return 0; }
        $sql = "SELECT COUNT(*) FROM tag WHERE owner_id = ".$clientId." AND tag_status = 'UNASSIGNED'";
        $result = $em->getConnection()->query($sql)->fetch();
        return $result == false || $result == null ? 0 : $result['count'];
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
     * @param Mate|DeclareNsfoBase $declaration
     * @return bool
     */
    private static function countNsfoAsErrorResponse($declaration)
    {
        if($declaration->getRequestState() == RequestStateType::FAILED && !$declaration->getIsHidden()) {
            return true;
        } else {
            return false;
        }
    }
    

    /**
     * Return an ArrayCollection with keys:
     * - pedigree
     * - non-pedigree
     * - total
     * having an integer value for the amount of animals in that category.
     *
     * @param Location $location
     * @param boolean $returnArrayWithLowerCaseKeys
     * @return ArrayCollection|array
     */
    public static function getLiveStockCountLocation(Location $location, $returnArrayWithLowerCaseKeys = false)
    {
        //Settings
        $isAlive = true;
        $isDepartedOption = false;
        $isExportedOption = false;

        //Initialize counters
        $pedigreeAdults = 0;
        $pedigreeLambs = 0;
        $nonPedigreeAdults = 0;
        $nonPedigreeLambs = 0;

        $adultDateOfBirthLimit = Utils::getAdultDateOfBirthLimit();

        foreach($location->getAnimals() as $animal) {

            $isOwnedAnimal =  Count::includeAnimal($animal, $isAlive, $isExportedOption, $isDepartedOption);

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

        if($returnArrayWithLowerCaseKeys) {
            $count = []; 
            $count[strtolower(LiveStockType::PEDIGREE_ADULT)] = $pedigreeAdults;
            $count[strtolower(LiveStockType::PEDIGREE_LAMB)] = $pedigreeLambs;
            $count[strtolower(LiveStockType::PEDIGREE_TOTAL)] = $pedigreeTotal;
            $count[strtolower(LiveStockType::NON_PEDIGREE_ADULT)] = $nonPedigreeAdults;
            $count[strtolower(LiveStockType::NON_PEDIGREE_LAMB)] = $nonPedigreeLambs;
            $count[strtolower(LiveStockType::NON_PEDIGREE_TOTAL)] = $nonPedigreeTotal;
            $count[strtolower(LiveStockType::ADULT)] = $nonPedigreeAdults + $pedigreeAdults;
            $count[strtolower(LiveStockType::LAMB)] = $nonPedigreeLambs + $pedigreeLambs;
            $count[strtolower(LiveStockType::TOTAL)] = $nonPedigreeTotal + $pedigreeTotal;

        } else {
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
        }

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

        //Initialize counters
        $pedigreeAdults = 0;
        $pedigreeLambs = 0;
        $nonPedigreeAdults = 0;
        $nonPedigreeLambs = 0;

        $adultDateOfBirthLimit = Utils::getAdultDateOfBirthLimit();

        foreach($client->getCompanies() as $company) {
            /** @var Location $location */
            foreach($company->getLocations() as $location) {
                /** @var Animal $animal */
                foreach($location->getAnimals() as $animal) {

                    $isOwnedAnimal = Count::includeAnimal($animal, $isAlive, $isExportedOption, $isDepartedOption);

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

    /**
     * Return an ArrayCollection with keys:
     * - ram
     * - ewe
     * - total
     * having an integer value for the amount of animals in that category.
     *
     * @param Company $company
     * @return ArrayCollection
     */
    public static function getCompanyLiveStockCount(Company $company)
    {

        //Settings
        $isAlive = true;
        $isDepartedOption = false;
        $isExportedOption = false;

        //Initialize counters
        $ramUnderSix = 0;
        $ramBetweenSixAndTwelve = 0;
        $ramOverTwelve = 0;

        $eweUnderSix = 0;
        $eweBetweenSixAndTwelve = 0;
        $eweOverTwelve = 0;

        $neuterUnderSix = 0;
        $neuterBetweenSixAndTwelve = 0;
        $neuterOverTwelve = 0;


        foreach($company->getLocations() as $location) {
            /** @var Animal $animal */
            foreach($location->getAnimals() as $animal) {
                $isOwnedAnimal = Count::includeAnimal($animal, $isAlive, $isExportedOption, $isDepartedOption);
                $dateOfBirth = $animal->getDateOfBirth();

                // TODO Change Gender when switching to CLASS based distinction
                $gender = $animal->getGender();

                $now = new \DateTime();
                $diff = $now->diff($dateOfBirth);

                if($isOwnedAnimal) {
                    if($gender == 'MALE') {
                        if($diff->y == 0 && $diff->m < 6) {
                           $ramUnderSix++;
                        }
                        if($diff->y == 0 && ($diff->m > 6 && $diff->m < 12)) {
                           $ramBetweenSixAndTwelve++;
                        }
                        if($diff->y > 0) {
                           $ramOverTwelve++;
                        }
                    }
                    if($gender == 'FEMALE') {
                        if($diff->y == 0 && $diff->m < 6) {
                            $eweUnderSix++;
                        }
                        if($diff->y == 0 && ($diff->m > 6 && $diff->m < 12)) {
                            $eweBetweenSixAndTwelve++;
                        }
                        if($diff->y > 0) {
                            $eweOverTwelve++;
                        }
                    }
                    if($gender == 'NEUTER') {
                        if($diff->y == 0 && $diff->m < 6) {
                            $neuterUnderSix++;
                        }
                        if($diff->y == 0 && ($diff->m > 6 && $diff->m < 12)) {
                            $neuterBetweenSixAndTwelve++;
                        }
                        if($diff->y > 0) {
                            $neuterOverTwelve++;
                        }
                    }
                }

            }
        }

        $ramTotal = $ramUnderSix + $ramBetweenSixAndTwelve + $ramOverTwelve;
        $eweTotal = $eweUnderSix + $eweBetweenSixAndTwelve + $eweOverTwelve;
        $neuterTotal = $neuterUnderSix + $neuterBetweenSixAndTwelve + $neuterOverTwelve;

        $count = new ArrayCollection();
        $count->set("RAM_TOTAL", $ramTotal);
        $count->set("RAM_UNDER_SIX", $ramUnderSix);
        $count->set("RAM_BETWEEN_SIX_AND_TWELVE", $ramBetweenSixAndTwelve);
        $count->set("RAM_OVER_TWELVE", $ramOverTwelve);

        $count->set("EWE_TOTAL", $eweTotal);
        $count->set("EWE_UNDER_SIX", $eweUnderSix);
        $count->set("EWE_BETWEEN_SIX_AND_TWELVE", $eweBetweenSixAndTwelve);
        $count->set("EWE_OVER_TWELVE", $eweOverTwelve);

        $count->set("NEUTER_TOTAL", $neuterTotal);
        $count->set("NEUTER_UNDER_SIX", $neuterUnderSix);
        $count->set("NEUTER_BETWEEN_SIX_AND_TWELVE", $neuterBetweenSixAndTwelve);
        $count->set("NEUTER_OVER_TWELVE", $neuterOverTwelve);

        return $count;
    }


    /**
     * @param Animal $animal
     * @param boolean $isAlive
     * @param boolean $isExported
     * @param boolean $isDeparted
     * @return bool
     */
    public static function includeAnimal(Animal $animal, $isAlive, $isExported, $isDeparted)
    {
        return $animal->getIsAlive() == $isAlive
            && $animal->getIsExportAnimal() == $isExported
            && $animal->getIsDepartedAnimal() == $isDeparted
            && ($animal->getTransferState() == AnimalTransferStatus::NULL
                || $animal->getTransferState() == AnimalTransferStatus::TRANSFERRED);
    }
}