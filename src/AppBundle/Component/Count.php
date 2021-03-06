<?php

namespace AppBundle\Component;


use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\Location;
use AppBundle\Entity\Mate;
use AppBundle\Enumerator\AnimalTransferStatus;
use AppBundle\Enumerator\LiveStockType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\RequestTypeNonIR;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;


/**
 * Class Count
 * @package AppBundle\Component
 */
class Count
{
    const FREE = 'free';
    const USED = 'used';

    /**
     * @param ObjectManager $em
     * @param Location $location
     * @return ArrayCollection
     */
    public static function getErrorCountDeclarationsPerLocation(ObjectManager $em, Location $location)
    {
        $errorCounts = new ArrayCollection();

        $locationId = $location->getId();
        if(!is_int($locationId)) {
            $defaultErrorCount = 0;
            $errorCounts->set(RequestType::DECLARE_ARRIVAL, $defaultErrorCount);
            $errorCounts->set(RequestType::DECLARE_DEPART, $defaultErrorCount);
            $errorCounts->set(RequestType::DECLARE_LOSS, $defaultErrorCount);
            $errorCounts->set(RequestType::DECLARE_BIRTH, $defaultErrorCount);
            $errorCounts->set(RequestTypeNonIR::MATE, $defaultErrorCount);
            return $errorCounts;
        }

        $sql = "SELECT COUNT(*), type
                FROM declare_base b
                INNER JOIN
                  declare_arrival x ON b.id = x.id
                WHERE b.request_state = '".RequestStateType::FAILED."' AND b.hide_failed_message = FALSE AND x.location_id = ".$locationId."
                GROUP BY type
                UNION
                SELECT COUNT(*), type
                FROM declare_base b
                  INNER JOIN
                  declare_import x ON b.id = x.id
                WHERE b.request_state = '".RequestStateType::FAILED."' AND b.hide_failed_message = FALSE AND x.location_id = ".$locationId."
                GROUP BY type
                UNION
                SELECT COUNT(*), type
                FROM declare_base b
                  INNER JOIN
                  declare_depart x ON b.id = x.id
                WHERE b.request_state = '".RequestStateType::FAILED."' AND b.hide_failed_message = FALSE AND x.location_id = ".$locationId."
                GROUP BY type
                UNION
                SELECT COUNT(*), type
                FROM declare_base b
                  INNER JOIN
                  declare_export x ON b.id = x.id
                WHERE b.request_state = '".RequestStateType::FAILED."' AND b.hide_failed_message = FALSE AND x.location_id = ".$locationId."
                GROUP BY type
                UNION
                SELECT COUNT(*), type
                FROM declare_base b
                  INNER JOIN
                  declare_loss x ON b.id = x.id
                WHERE b.request_state = '".RequestStateType::FAILED."' AND b.hide_failed_message = FALSE AND x.location_id = ".$locationId."
                GROUP BY type
                UNION
                SELECT COUNT(*), type
                FROM declare_base b
                  INNER JOIN
                  declare_birth x ON b.id = x.id
                WHERE b.request_state = '".RequestStateType::FAILED."' AND b.hide_failed_message = FALSE AND x.location_id = ".$locationId."
                GROUP BY type
                UNION
                SELECT COUNT(*), type
                FROM declare_base b
                  INNER JOIN
                  declare_tags_transfer x ON b.id = x.id
                WHERE b.request_state = '".RequestStateType::FAILED."' AND b.hide_failed_message = FALSE AND x.location_id = ".$locationId."
                GROUP BY type
                UNION
                SELECT COUNT(*), type
                FROM mate m
                  INNER JOIN declare_nsfo_base b ON m.id = b.id
                WHERE b.request_state = '".RequestStateType::FAILED."' AND b.is_hidden = FALSE AND m.location_id = ".$locationId."
                GROUP BY type";

        $results = $em->getConnection()->query($sql)->fetchAll();

        $searchArray = [];
        foreach ($results as $result) {
            $searchArray[$result['type']] = $result['count'];
        }
        
        $errorCounts->set(RequestType::DECLARE_ARRIVAL, self::getCountFromSearchArray(DeclareArrival::class, $searchArray)
                                                      + self::getCountFromSearchArray(DeclareImport::class, $searchArray));
        $errorCounts->set(RequestType::DECLARE_DEPART, self::getCountFromSearchArray(DeclareDepart::class, $searchArray)
                                                     + self::getCountFromSearchArray(DeclareExport::class, $searchArray));
        $errorCounts->set(RequestType::DECLARE_LOSS, self::getCountFromSearchArray(DeclareLoss::class, $searchArray));
        $errorCounts->set(RequestType::DECLARE_BIRTH, self::getCountFromSearchArray(DeclareBirth::class, $searchArray));
        $errorCounts->set(RequestTypeNonIR::MATE, self::getCountFromSearchArray(Mate::class, $searchArray));

        return $errorCounts;
    }


    /**
     * @param string $classPath
     * @param array $searchArray
     * @return int
     */
    private static function getCountFromSearchArray($classPath, $searchArray)
    {
        $key = StringUtil::getEntityName($classPath);

        if(array_key_exists($key, $searchArray)){
            return $searchArray[$key];
        } else {
            return 0;
        }
    }


    /**
     * @param EntityManagerInterface $em
     * @param int $clientId
     * @param int|null $locationId if set to null than all tags linked to the clientId are used for the counts
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function getTagsCount(EntityManagerInterface $em, $clientId, $locationId)
    {
        if(!is_int($clientId)) { return 0; }

        $locationIdFilter = '';
        if (is_int($locationId)) {
            $locationIdFilter = " AND tag.location_id = ".$locationId." ";
        }

        $sql = "SELECT COUNT(*), '".self::FREE."' as type 
                FROM tag 
                WHERE owner_id = ".$clientId." ".$locationIdFilter." 
                    AND tag_status = '".TagStateType::UNASSIGNED."'
                UNION
                SELECT COUNT(*), '".self::USED."' as type 
                FROM tag 
                WHERE owner_id = ".$clientId." ".$locationIdFilter." 
                    AND tag_status <> '".TagStateType::UNASSIGNED."'";
        $results = $em->getConnection()->query($sql)->fetchAll();

        $counts = [];
        foreach ($results as $result) {
            $counts[$result['type']] = $result['count'];
        }

        return $counts;
    }
    

    /**
     * Return an ArrayCollection with keys:
     * - pedigree
     * - non-pedigree
     * - total
     * having an integer value for the amount of animals in that category.
     *
     * @param ObjectManager $em
     * @param Location $location
     * @param boolean $returnArrayWithLowerCaseKeys
     * @return ArrayCollection|array
     */
    public static function getLiveStockCountLocation(ObjectManager $em, Location $location, $returnArrayWithLowerCaseKeys = false)
    {
        $locationId = $location->getId();
        if(!is_int($locationId)) { return 0; }
        
        $adultDateOfBirthLimit = Utils::getAdultDateStringOfBirthLimit();

        $sql = "SELECT COUNT(animal.id), '".LiveStockType::PEDIGREE_ADULT."' as type FROM animal
                    LEFT JOIN pedigree_register r ON animal.pedigree_register_id = r.id
                WHERE is_alive = TRUE
                    AND (transfer_state ISNULL OR transfer_state = '".AnimalTransferStatus::TRANSFERRED."')
                    AND location_id = ".$locationId."
                    AND pedigree_country_code NOTNULL AND animal.pedigree_number NOTNULL
                    AND animal.date_of_birth <= '".$adultDateOfBirthLimit."'
                    AND r.is_registered_with_nsfo = TRUE
                UNION
                SELECT COUNT(animal.id), '".LiveStockType::PEDIGREE_TOTAL."' as type FROM animal
                    LEFT JOIN pedigree_register r ON animal.pedigree_register_id = r.id
                WHERE is_alive = TRUE
                    AND (transfer_state ISNULL OR transfer_state = '".AnimalTransferStatus::TRANSFERRED."')
                    AND location_id = ".$locationId."
                    AND pedigree_country_code NOTNULL AND animal.pedigree_number NOTNULL
                    AND r.is_registered_with_nsfo = TRUE
                UNION
                SELECT COUNT(animal.id), '".LiveStockType::NON_PEDIGREE_ADULT."' as type FROM animal
                    LEFT JOIN pedigree_register r ON animal.pedigree_register_id = r.id
                WHERE is_alive = TRUE
                    AND (transfer_state ISNULL OR transfer_state = '".AnimalTransferStatus::TRANSFERRED."')
                    AND location_id = ".$locationId."
                    AND ((pedigree_country_code ISNULL OR animal.pedigree_number ISNULL) OR
                         (r.is_registered_with_nsfo = FALSE OR r.is_registered_with_nsfo ISNULL))
                    AND animal.date_of_birth <= '".$adultDateOfBirthLimit."'
                UNION
                SELECT COUNT(animal.id), '".LiveStockType::NON_PEDIGREE_TOTAL."' as type FROM animal
                    LEFT JOIN pedigree_register r ON animal.pedigree_register_id = r.id
                WHERE is_alive = TRUE
                    AND (transfer_state ISNULL OR transfer_state = '".AnimalTransferStatus::TRANSFERRED."')
                    AND location_id = ".$locationId."
                    AND ((pedigree_country_code ISNULL OR animal.pedigree_number ISNULL) OR
                         (r.is_registered_with_nsfo = FALSE OR r.is_registered_with_nsfo ISNULL))";
        $results = $em->getConnection()->query($sql)->fetchAll();

        $searchArray = [];
        foreach ($results as $result) {
            $searchArray[$result['type']] = $result['count'];
        }

        $pedigreeAdults = $searchArray[LiveStockType::PEDIGREE_ADULT];
        $pedigreeTotal = $searchArray[LiveStockType::PEDIGREE_TOTAL];
        $pedigreeLambs = $pedigreeTotal - $pedigreeAdults;

        $nonPedigreeAdults = $searchArray[LiveStockType::NON_PEDIGREE_ADULT];
        $nonPedigreeTotal = $searchArray[LiveStockType::NON_PEDIGREE_TOTAL];
        $nonPedigreeLambs = $nonPedigreeTotal - $nonPedigreeAdults;

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

                    //A pedigree animal must be part of a pedigreeRegister registered with NSFO and must have a pedigree number
                    $isPedigree = false;
                    $pedigreeRegister = $animal->getPedigreeRegister();
                    if($pedigreeRegister) {
                        if($pedigreeRegister->getIsRegisteredWithNsfo()) {
                            $isPedigree = $animal->getPedigreeCountryCode() != null
                                && $animal->getPedigreeNumber() != null;
                        }
                    }

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
                        if($diff->y == 0 && ($diff->m >= 6 && $diff->m <= 12)) {
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
                        if($diff->y == 0 && ($diff->m >= 6 && $diff->m <= 12)) {
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
                        if($diff->y == 0 && ($diff->m >= 6 && $diff->m <= 12)) {
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
