<?php

namespace AppBundle\Entity;
use AppBundle\Component\Count;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Enumerator\AnimalTransferStatus;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\LiveStockType;
use AppBundle\Util\AnimalArrayReader;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AnimalRepository
 * @package AppBundle\Entity
 */
class AnimalRepository extends BaseRepository
{
  /**
   * @param $Id
   * @return null|Animal|Ram|Ewe|Neuter
   */
  function findByUlnOrPedigree($Id, $isUln)
  {
    //Strip countryCode
    $countryCode = mb_substr($Id, 0, 2, 'utf-8');

    //Strip ulnCode or pedigreeCode
    $ulnOrPedigreeCode = mb_substr($Id, 2, strlen($Id));

    if($isUln) {
      return $this->findByUlnCountryCodeAndNumber($countryCode, $ulnOrPedigreeCode);

    } else {
      return $this->findByPedigreeCountryCodeAndNumber($countryCode, $ulnOrPedigreeCode);
    }
  }

  /**
   * @param $countryCode
   * @param $pedigreeNumber
   * @return null|Animal|Ram|Ewe|Neuter
   */
  public function findByPedigreeCountryCodeAndNumber($countryCode, $pedigreeNumber)
  {
    $animal = $this->findOneBy(array('pedigreeCountryCode' => $countryCode, 'pedigreeNumber' => $pedigreeNumber));

    return $animal;
  }

  /**
   * @param $countryCode
   * @param $ulnNumber
   * @return null|Animal|Ram|Ewe|Neuter
   */
  public function findByUlnCountryCodeAndNumber($countryCode, $ulnNumber)
  {
    $animals = $this->findBy(array('ulnCountryCode' => $countryCode, 'ulnNumber' => $ulnNumber));
    return AnimalArrayReader::prioritizeImportedAnimalFromArray($animals);
  }


  /**
   * @param string $countryCode
   * @param string $ulnNumber
   * @return int
   */
  public function sqlQueryAnimalIdByUlnCountryCodeAndNumber($countryCode, $ulnNumber)
  {
    $sql = "SELECT id, name FROM animal WHERE uln_country_code = '".$countryCode."' AND uln_number = '".$ulnNumber."'";
    $results = $this->getManager()->getConnection()->query($sql)->fetchAll();
    if(count($results) == 1) {
      return $results[0]['id'];

    } elseif(count($results) == 0) {
      return null;

    } else {
      //in case of duplicate uln, use the imported animal
      foreach ($results as $result) {
        if($result['name'] != null) {
          return $result['id'];
        }
      }
      //If none of the animals are imported, just take the first animal
      return $results[0]['id'];
    }
  }
  

  /**
   * @param $animalType
   * @param array $filterArray
   * @return Animal[]|Ewe[]|Neuter[]|Ram[]|array|null
   */
  public function findByTypeOrState($animalType, array $filterArray)
  {
    $animals = null;

    //select all animals, belonging to user with no filters
    if ($animalType == null && !array_key_exists(Constant::IS_ALIVE_NAMESPACE, $filterArray)) {
      $animals = $this->getManager()->getRepository(Constant::ANIMAL_REPOSITORY)->findBy($filterArray);
    } else if ($animalType == null && array_key_exists(Constant::IS_ALIVE_NAMESPACE, $filterArray)) {
      //filter animals by given isAlive state:{true, false}, belonging to user
      $animals = $this->getManager()->getRepository(Constant::ANIMAL_REPOSITORY)->findBy($filterArray);
    } else if ($animalType != null) {
      //filter animals by given animal-type:{ram, ewe, neuter}, belonging to user
      switch ($animalType) {
        case AnimalObjectType::EWE:
          $animals = $this->getManager()->getRepository(Constant::EWE_REPOSITORY)->findBy($filterArray);
          break;
        case AnimalObjectType::RAM:
          $animals = $this->getManager()->getRepository(Constant::RAM_REPOSITORY)->findBy($filterArray);
          break;
        case AnimalObjectType::NEUTER:
          $animals = $this->getManager()->getRepository(Constant::NEUTER_REPOSITORY)->findBy($filterArray);
          break;
        default:
          break;
      }
    }

    return $animals;
  }

  /**
   * @param Animal $animal
   * @return Animal|null
   */
  public function findByAnimal(Animal $animal = null)
  {
    if ($animal == null) {
      return null;
    }

    $countryCode = $animal->getUlnCountryCode();
    $number = $animal->getUlnNumber();
    $retrievedAnimal = $this->findByUlnCountryCodeAndNumber($countryCode, $number);

    if ($retrievedAnimal != null) {
      return $retrievedAnimal;
    } else {
      $countryCode = $animal->getPedigreeCountryCode();
      $number = $animal->getPedigreeNumber();
      $retrievedAnimal = $this->findByPedigreeCountryCodeAndNumber($countryCode, $number);
    }

    return $retrievedAnimal;
  }

  /**
   * @param Client $client
   * @param null|string $animalType
   * @param null|boolean $isAlive
   * @param null|boolean $includeDepartedAndExported
   * @return Animal[]|Ewe[]|Neuter[]|Ram[]|array|null
   */
  public function findOfClientByAnimalTypeAndIsAlive(Client $client, $animalType = null, $isAlive = null)
  {
    $animals = null;
    $locationRepository = $this->getManager()
        ->getRepository(Constant::LOCATION_REPOSITORY);

    //Get locations of user
    $locations = $locationRepository->findByUser($client);

    //Get animals on each location belonging to user
    foreach ($locations as $location) {
      $filterArray = array(Constant::LOCATION_NAMESPACE => $location->getId());

      //select all animals, belonging to user with no filters
      if ($animalType == null && $isAlive == null) {
        $animals = $this->findByTypeOrState(null, $filterArray);

        //filter animals by given isAlive state:{true, false}, belonging to user
      } else if ($animalType == null && $isAlive != null) {
        $filterArray = array(
            Constant::LOCATION_NAMESPACE => $location->getId(),
            Constant::IS_ALIVE_NAMESPACE => ($isAlive === Constant::BOOLEAN_TRUE_NAMESPACE)
        );

      } else if ($animalType != null && $isAlive == null) {
        $animals = $this->findByTypeOrState($animalType, $filterArray);

        //filter animals by given animal-type: {ram, ewe, neuter} and isAlive state: {true, false}, belonging to user
      } else {
        $filterArray = array(
            Constant::LOCATION_NAMESPACE => $location->getId(),
            Constant::IS_ALIVE_NAMESPACE => ($isAlive === Constant::BOOLEAN_TRUE_NAMESPACE)
        );
      }
      $animals = $this->findByTypeOrState($animalType, $filterArray);
    }

    return $animals;
  }

  /**
   * @param Client $client
   * @param array $animalArray
   * @return boolean|null
   */
  public function verifyIfClientOwnsAnimal(Client $client, $animalArray)
  {
    $ulnExists = array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $animalArray) &&
        array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $animalArray);
    $pedigreeExists = array_key_exists(Constant::PEDIGREE_NUMBER_NAMESPACE, $animalArray) &&
        array_key_exists(Constant::PEDIGREE_NUMBER_NAMESPACE, $animalArray);

    if ($ulnExists) {
      $numberToCheck = $animalArray[Constant::ULN_NUMBER_NAMESPACE];
      $countryCodeToCheck = $animalArray[Constant::ULN_COUNTRY_CODE_NAMESPACE];

    } else if ($pedigreeExists) {
      $numberToCheck = $animalArray[Constant::PEDIGREE_NUMBER_NAMESPACE];
      $countryCodeToCheck = $animalArray[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE];

    } else {
      return null;
    }

    foreach ($client->getCompanies() as $company) {
      foreach ($company->getLocations() as $location) {
        foreach ($location->getAnimals() as $animal) {

          if ($ulnExists) {
            $ulnNumber = $animal->getUlnNumber();
            $ulnCountryCode = $animal->getUlnCountryCode();
            if ($ulnNumber == $numberToCheck && $ulnCountryCode == $countryCodeToCheck) {
              return true;
            }

          } else if ($pedigreeExists) {
            $pedigreeNumber = $animal->getPedigreeNumber();
            $pedigreeCountryCode = $animal->getPedigreeCountryCode();
            if ($pedigreeNumber == $numberToCheck && $pedigreeCountryCode == $countryCodeToCheck) {
              return true;
            }
          }

        }
      }
    }

    return false;
  }

  /**
   * @param Client $client
   * @param bool $isAlive
   * @param bool $isDeparted
   * @param bool $isExported
   * @return array
   */
  public function getLiveStockAllLocations(Client $client, $isAlive = true, $isDeparted = false, $isExported = false)
  {
    $animals = array();

    foreach ($client->getCompanies() as $company) {
      /** @var Location $location */
      foreach ($company->getLocations() as $location) {
        /** @var Animal $animal */
        foreach ($location->getAnimals() as $animal) {

          $showAnimal = Count::includeAnimal($animal, $isAlive, $isExported, $isDeparted);

          if ($showAnimal) {
            $animals[] = $animal;
          }

        }
      }
    }

    return $animals;
  }


  /**
   * @param bool $isIncludingOnlyAliveAnimals
   * @param string $nullFiller
   * @return array
   */
  public function getAllRams($isIncludingOnlyAliveAnimals = true, $nullFiller = '')
  {
    $extraFilter = "";
    if($isIncludingOnlyAliveAnimals) { $extraFilter = " AND is_alive = true"; }

    $sql = "SELECT a.uln_country_code, a.uln_number, a.pedigree_country_code, a.pedigree_number, a.animal_order_number as work_number,
                   a.date_of_birth, l.ubn, a.is_alive FROM animal a 
                   LEFT JOIN location l ON l.id = a.location_id
                   WHERE type = 'Ram'";
    $animalsData = $this->getManager()->getConnection()->query($sql.$extraFilter)->fetchAll();

    $results = [];
    //Resetting the values in another array to include null checks
    foreach ($animalsData as $result) {
      $results[] = [
        JsonInputConstant::ULN_COUNTRY_CODE => Utils::fillNullOrEmptyString($result['uln_country_code'], $nullFiller),
        JsonInputConstant::ULN_NUMBER => Utils::fillNullOrEmptyString($result['uln_number'], $nullFiller),
        JsonInputConstant::PEDIGREE_COUNTRY_CODE => Utils::fillNullOrEmptyString($result['pedigree_country_code'], $nullFiller),
        JsonInputConstant::PEDIGREE_NUMBER => Utils::fillNullOrEmptyString($result['pedigree_number'], $nullFiller),
        JsonInputConstant::WORK_NUMBER => Utils::fillNullOrEmptyString($result['work_number'], $nullFiller),
        JsonInputConstant::DATE_OF_BIRTH => Utils::fillNullOrEmptyString($result['date_of_birth'], $nullFiller),
        JsonInputConstant::UBN => Utils::fillNullOrEmptyString($result['ubn'], $nullFiller),
        JsonInputConstant::IS_ALIVE => Utils::fillNullOrEmptyString($result['is_alive'], $nullFiller),
      ];
    }
    
    return $results;
  }


  /**
   * @param Location $location
   * @param bool $isAlive
   * @param bool $isDeparted
   * @param bool $isExported
   * @return array
   */
  public function getLiveStock(Location $location, $isAlive = true, $isDeparted = false, $isExported = false)
  {
    $animals = array();

    foreach ($location->getAnimals() as $animal) {

      $showAnimal = Count::includeAnimal($animal, $isAlive, $isExported, $isDeparted);

      if ($showAnimal) {
        $animals[] = $animal;
      }
    }

    return $animals;
  }


  /**
   * @param Location $location
   * @param string $replacementString
   * @return array
   */
  public function getHistoricLiveStock(Location $location, $replacementString = '')
  {
    $results = [];

    // Null check
    if(!($location instanceof Location)) { return $results; }
    elseif (!is_int($location->getId())) { return $results; }

    $sql = "SELECT a.uln_country_code, a.uln_number, a.pedigree_country_code, a.pedigree_number, a.animal_order_number,
              a.gender, a.date_of_birth, a.is_alive, a.date_of_death, l.ubn
            FROM animal a
              INNER JOIN location l ON a.location_id = l.id
            WHERE a.location_id = ".$location->getId()."
            UNION
            SELECT a.uln_country_code, a.uln_number, a.pedigree_country_code, a.pedigree_number, a.animal_order_number,
              a.gender, a.date_of_birth, a.is_alive, a.date_of_death, l.ubn
            FROM animal_residence r
              INNER JOIN animal a ON r.animal_id = a.id
              LEFT JOIN location l ON a.location_id = l.id
              LEFT JOIN company c ON c.id = l.company_id
            WHERE r.location_id = ".$location->getId()." AND (c.is_reveal_historic_animals = TRUE OR a.location_id ISNULL)";
    $retrievedAnimalData = $this->getManager()->getConnection()->query($sql)->fetchAll();

    foreach ($retrievedAnimalData as $record) {
      $results[] = [
        JsonInputConstant::ULN_COUNTRY_CODE => Utils::fillNullOrEmptyString($record['uln_country_code'], $replacementString),
        JsonInputConstant::ULN_NUMBER => Utils::fillNullOrEmptyString($record['uln_number'], $replacementString),
        JsonInputConstant::PEDIGREE_COUNTRY_CODE => Utils::fillNullOrEmptyString($record['pedigree_country_code'], $replacementString),
        JsonInputConstant::PEDIGREE_NUMBER => Utils::fillNullOrEmptyString($record['pedigree_number'], $replacementString),
        JsonInputConstant::WORK_NUMBER => Utils::fillNullOrEmptyString($record['animal_order_number'], $replacementString),
        JsonInputConstant::GENDER => Utils::fillNullOrEmptyString($record['gender'], $replacementString),
        JsonInputConstant::DATE_OF_BIRTH => Utils::fillNullOrEmptyString($record['date_of_birth'], $replacementString),
        JsonInputConstant::DATE_OF_DEATH => Utils::fillNullOrEmptyString($record['date_of_death'], $replacementString),
        JsonInputConstant::IS_ALIVE => Utils::fillNullOrEmptyString($record['is_alive'], $replacementString),
        JsonInputConstant::UBN => Utils::fillNullOrEmptyString($record['ubn'], $replacementString),
      ];
    }

    return $results;
  }


  /**
   * @param int $locationId
   * @return array
   */
  public function getLiveStockBySql($locationId)
  {
//    $sql = "SELECT a.id, a.uln_country_code, a.uln_number, a.pedigree_country_code, a.pedigree_number, a.animal_order_number as work_number,
//                   a.gender, a.date_of_birth, a.is_alive, a.is_departed_animal, c.last_weight as weight, c.weight_measurement_date
//            FROM animal a
//            LEFT JOIN animal_cache c ON a.id = c.animal_id
//            WHERE is_departed_animal = FALSE AND a.is_alive = TRUE AND (a.transfer_state ISNULL OR a.transfer_state = '') AND a.location_id = ".$locationId;


      $sql = " SELECT DISTINCT a.id, a.uln_country_code, a.uln_number, a.pedigree_country_code, a.pedigree_number, a.animal_order_number as work_number,
                      a.gender, a.date_of_birth, a.is_alive, a.is_departed_animal, c.last_weight as weight, c.weight_measurement_date
              FROM animal a
              LEFT JOIN animal_cache c ON a.id = c.animal_id
              WHERE is_departed_animal = FALSE AND a.is_alive = TRUE AND (a.transfer_state ISNULL OR a.transfer_state = '') AND a.location_id = ".$locationId;

    $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

    $results = NullChecker::replaceNullInNestedArray($results);

    return $results;
  }
  

  /**
   * @param Client $client
   * @param string $ulnString
   * @return null|Animal
   */
  public function getAnimalByUlnString(Client $client, $ulnString)
  {
    $uln = Utils::getUlnFromString($ulnString);

    if ($uln == null) { //invalid input for $ulnString
      return null;
    }

    foreach ($client->getCompanies() as $company) {
      /** @var Location $location */
      foreach ($company->getLocations() as $location) {
        foreach ($location->getAnimals() as $animal) {

          $showAnimal = $animal->getUlnCountryCode() == $uln[Constant::ULN_COUNTRY_CODE_NAMESPACE]
              && $animal->getUlnNumber() == $uln[Constant::ULN_NUMBER_NAMESPACE];

          if ($showAnimal) {
            return $animal;
          }

        }
      }
    }

    return null;
  }


  /**
   * @param string $ulnString
   * @return Animal|Ewe|Neuter|Ram|null
   */
  public function findAnimalByUlnString($ulnString)
  {
    $uln = Utils::getUlnFromString($ulnString);
    return $this->findByUlnCountryCodeAndNumber($uln[Constant::ULN_COUNTRY_CODE_NAMESPACE], $uln[Constant::ULN_NUMBER_NAMESPACE] );
  }

  /**
   * @param string $pedigreeCountryCode
   * @param string $pedigreeNumber
   * @return array
   */
  public function getUlnByPedigree($pedigreeCountryCode, $pedigreeNumber)
  {
    $animal = $this->findByPedigreeCountryCodeAndNumber($pedigreeCountryCode, $pedigreeNumber);

    if($animal!=null) {
      $ulnCountryCode = $animal->getUlnCountryCode();
      $ulnNumber = $animal->getUlnNumber();
    } else {
      $ulnCountryCode = null;
      $ulnNumber = null;
    }

    return array(Constant::ULN_COUNTRY_CODE_NAMESPACE => $ulnCountryCode,
                 Constant::ULN_NUMBER_NAMESPACE => $ulnNumber);
  }

   /**
   * @param string $ulnCountryCode
   * @param string $ulnNumber
   * @return array
   */
    public function getPedigreeByUln($ulnCountryCode, $ulnNumber)
    {
      $animal = $this->findByUlnCountryCodeAndNumber($ulnCountryCode, $ulnNumber);

      if($animal!=null) {
        $pedigreeCountryCode = $animal->getPedigreeCountryCode();
        $pedigreeNumber = $animal->getPedigreeNumber();
      } else {
        $pedigreeCountryCode = null;
        $pedigreeNumber = null;
      }

      return array(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE => $pedigreeCountryCode,
          Constant::PEDIGREE_NUMBER_NAMESPACE => $pedigreeNumber);
    }

   /**
    * @param $startId
    * @param $endId
    * @return Collection
    */
    public function getAnimalsById($startId, $endId)
    {
      $criteria = Criteria::create()
          ->where(Criteria::expr()->gte('id', $startId))
          ->andWhere(Criteria::expr()->lte('id', $endId))
          ->orderBy(['id' => Criteria::ASC])
      ;

      return $this->getManager()->getRepository(Animal::class)
                  ->matching($criteria);
    }


  /**
   * @param $startId
   * @param $endId
   * @return Collection
   */
  public function getAnimalsByIdWithoutBreedCodesSetForExistingBreedCode($startId, $endId)
  {
    $criteria = Criteria::create()
        ->where(Criteria::expr()->gte('id', $startId))
        ->andWhere(Criteria::expr()->lte('id', $endId))
        ->andWhere(Criteria::expr()->isNull('breedCodes'))
        ->andWhere(Criteria::expr()->neq('breedCode', null))
        ->orderBy(['id' => Criteria::ASC])
    ;

    return $this->getManager()->getRepository(Animal::class)
        ->matching($criteria);
  }

  /**
   * @return int|null
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getMaxId()
  {
    $sql = "SELECT MAX(id) FROM animal";
    return $this->executeSqlQuery($sql);
  }
  
  /**
   * @return int|null
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getMaxVsmId()
  {
    $sql = "SELECT MAX(name) FROM animal";
    return $this->executeSqlQuery($sql);
  }

  /**
   * @return int|null
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getMinIdOfAnimalsWithoutBreedCodesSetForExistingBreedCode()
  {
    $sql = "SELECT MIN(id) FROM animal WHERE (breed_codes_id IS NULL AND breed_code IS NOT NULL)";
    return $this->executeSqlQuery($sql);
  }

  /**
   * @param array $animalArray
   * @return Animal|Ewe|Neuter|Ram|null
   */
  public function findAnimalByAnimalArray($animalArray)
  {
    $ulnCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_COUNTRY_CODE, $animalArray);
    $ulnNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_NUMBER, $animalArray);
    if ($ulnCountryCode != null && $ulnNumber != null) {
      return $this->findByUlnCountryCodeAndNumber($ulnCountryCode, $ulnNumber);
    }

    $pedigreeCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $animalArray);
    $pedigreeNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_NUMBER, $animalArray);
    if ($pedigreeCountryCode != null && $pedigreeNumber != null) {
      return $this->findByPedigreeCountryCodeAndNumber($pedigreeCountryCode, $pedigreeNumber);
    }

    //else
    return null;
  }

  /**
   * @return int|null
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getMinIdOfAnimalsWithoutPedigreeNumberOrPedigreeCountryCode()
  {
    $sql = "SELECT MIN(id) FROM animal WHERE (animal.pedigree_country_code IS NULL OR animal.pedigree_number IS NULL)";
    return $this->executeSqlQuery($sql);
  }

  /**
   * @return ArrayCollection
   */
  public function getAnimalPrimaryKeysByVsmId()
  {
    $sql = "SELECT id, name FROM animal";
    $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

    $array = new ArrayCollection();
    foreach ($results as $result) {
      $array->set($result['name'], intval($result['id']));
    }

    return $array;
  }


  /**
   * @return array
   */
  public function getAnimalPrimaryKeysByVsmIdArray()
  {
    $sql = "SELECT name as vsm_id, id FROM animal WHERE name IS NOT NULL";
    $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

    $searchArray = array();
    foreach ($results as $result) {
      $searchArray[$result['vsm_id']] = $result['id'];
    }
    return $searchArray;
  }


  /**
   * @param int $animalId
   * @param  $measurementDateString
   * @return bool|null
   */
  public function isWithin3DaysAfterDateOfBirth($animalId, $measurementDateString)
  {
    if (TimeUtil::isFormatYYYYMMDD($measurementDateString)) {
      $sql = "SELECT DATE(date_of_birth) as date_of_birth FROM animal WHERE id = " . intval($animalId);
      $result = $this->getManager()->getConnection()->query($sql)->fetch();
      
      $dateOfBirth = new \DateTime($result['date_of_birth']);
      $measurementDate = new \DateTime($measurementDateString);
      $dateOfBirthPlus3Days = clone $dateOfBirth;
      $dateOfBirthPlus3Days->add(new \DateInterval('P3D'));

      if ($dateOfBirth <= $measurementDate && $measurementDate <= $dateOfBirthPlus3Days) {
        return true;
      } else {
        return false;
      }
    }

    return null;
  }


  /**
   * @param $animalId
   * @return int|null
   */
  public function getMotherId($animalId)
  {
    if(is_int($animalId)) {
      $sql = "SELECT parent_mother_id as id_mother FROM animal WHERE id = ".$animalId;
      $result = $this->getManager()->getConnection()->query($sql)->fetch();
      return $result['id_mother'];
    } else {
      return null;
    }
  }


  /**
   * @param $animalId
   * @return int|null
   */
  public function getFatherId($animalId)
  {
    if(is_int($animalId)) {
      $sql = "SELECT parent_father_id as id_father FROM animal WHERE id = ".$animalId;
      $result = $this->getManager()->getConnection()->query($sql)->fetch();
      return $result['id_father'];
    } else {
      return null;
    }

  }


  /**
   * @return ArrayCollection
   */
  public function getAnimalPrimaryKeysByUlnString($isCountryCodeSeparatedByString = false)
  {
    if($isCountryCodeSeparatedByString) {
      $ulnFormat = "uln_country_code,' ',uln_number";
    } else {
      $ulnFormat = "uln_country_code,uln_number";
    }
    $sql = "SELECT CONCAT(".$ulnFormat.") as uln, id, type FROM animal";
    $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

    $array = new ArrayCollection();
    foreach ($results as $result) {
      if($array->containsKey($result['uln'])) {
        if($result['type'] != 'Neuter') {
          $array->set($result['uln'], $result['id']);
        }
      } else {
        $array->set($result['uln'], $result['id']);
      }
    }

    return $array;
  }


  /**
   * @param Animal $animal
   * @param string $replacementString
   * @return array
   */
  public function getOffspringLogDataBySql($animal, $replacementString)
  {
    $results = [];

    if ($animal instanceof Ewe) {
      $filter = "parent_mother_id = " . $animal->getId();
    } elseif ($animal instanceof Ram) {
      $filter = "parent_father_id = " . $animal->getId();
    } else {
      return $results;
    }

    $sql = "SELECT uln_country_code, uln_number, pedigree_country_code, pedigree_number, gender, date_of_birth FROM animal
            WHERE " . $filter;
    $retrievedData = $this->getManager()->getConnection()->query($sql)->fetchAll();

    foreach ($retrievedData as $record) {
      $results[] = [
          JsonInputConstant::ULN_COUNTRY_CODE => Utils::fillNullOrEmptyString($record['uln_country_code'], $replacementString),
          JsonInputConstant::ULN_NUMBER => Utils::fillNullOrEmptyString($record['uln_number'], $replacementString),
          JsonInputConstant::PEDIGREE_COUNTRY_CODE => Utils::fillNullOrEmptyString($record['pedigree_country_code'], $replacementString),
          JsonInputConstant::PEDIGREE_NUMBER => Utils::fillNullOrEmptyString($record['pedigree_number'], $replacementString),
          JsonInputConstant::GENDER => Utils::fillNullOrEmptyString($record['gender'], $replacementString),
          JsonInputConstant::DATE_OF_BIRTH => Utils::fillNullOrEmptyString($record['date_of_birth'], $replacementString),
      ];
    }
    return $results;
  }


  /**
   * @param $animalId
   * @return int
   */
  public function getOffspringCount($animalId)
  {
    if(!is_int($animalId)) { return 0; }
    $sql = "SELECT COUNT(*) FROM animal a WHERE parent_father_id = ".$animalId." OR parent_mother_id = ".$animalId;
    return $this->getManager()->getConnection()->query($sql)->fetch()['count'];
  }


  /**
   * @param OutputInterface|null $output
   * @param CommandUtil|null $cmdUtil
   */
  public function deleteTestAnimal(OutputInterface $output = null, CommandUtil $cmdUtil = null)
  {
    if($output != null) { $output->writeln('Delete breedValuesSets of testAnimals'); }

    $sql = "SELECT id FROM animal WHERE uln_country_code = 'XD'";
    $results = $this->getManager()->getConnection()->query($sql)->fetchAll();
    foreach ($results as $result) {
      $animalId = intval($result['id']);
      $sql = "DELETE FROM breed_values_set WHERE animal_id = ".$animalId;
      $this->getManager()->getConnection()->exec($sql);
    }
    if($output != null) {
      $output->writeln('BreedValuesSets of testAnimals deleted'); 
      $output->writeln('Find all testAnimals...');
    }
    
    /** @var AnimalRepository $animalRepository */
    $animalRepository = $this->getManager()->getRepository(Animal::class);
    $testAnimals = $animalRepository->findBy(['ulnCountryCode' => 'XD']);

    $count = count($testAnimals);
    if($count == 0) {
      if($output != null) {
        $output->writeln('No testAnimals in Database');
      }
    }

    if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt($count + 1, 1, 'Deleting testAnimals'); }
    foreach ($testAnimals as $testAnimal) {
      $this->getManager()->remove($testAnimal);
      if($cmdUtil != null) {
        $cmdUtil->advanceProgressBar(1); 
      }
    }
    $this->getManager()->flush();
    if($cmdUtil != null) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }
  }
}
