<?php

namespace AppBundle\Entity;

use AppBundle\Component\Count;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Service\CacheService;
use AppBundle\Util\AnimalArrayReader;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Connection;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;
use Symfony\Component\Console\Output\OutputInterface;
use Snc\RedisBundle\Client\Phpredis\Client as PredisClient;

/**
 * Class AnimalRepository
 * @package AppBundle\Entity
 */
class AnimalRepository extends BaseRepository
{
  const BATCH = 1000;
  const USE_REDIS_CACHE = true; //TODO activate this when the livestock and historicLivestock redis cache is fixed
  const LIVESTOCK_CACHE_ID = 'GET_LIVESTOCK_';
  const HISTORIC_LIVESTOCK_CACHE_ID = 'GET_HISTORIC_LIVESTOCK_';
  const CANDIDATE_FATHERS_CACHE_ID = 'GET_CANDIDATE_FATHERS_';
  const CANDIDATE_SURROGATES_CACHE_ID = 'GET_CANDIDATE_SURROGATES_';

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
   * @param CacheService $cacheService
   * @param bool $isAlive
   * @param bool $isDeparted
   * @param bool $isExported
   * @param Animal $queryOnlyOnAnimalGenderType
   * 
   * @return array
   */
  public function getLiveStock(Location $location,
                               CacheService $cacheService,
                               $isAlive = true,
                               $isDeparted = false,
                               $isExported = false,
                               $queryOnlyOnAnimalGenderType = null)
  {
    $cacheId = AnimalRepository::LIVESTOCK_CACHE_ID . $location->getId(); //. sha1($location->getId());
    $isAlive = $isAlive ? 'true' : 'false';
    //unused
    $isDeparted = $isDeparted ? 'true' : 'false';
    $isExported = $isExported ? 'true' : 'false';

    $em = $this->getEntityManager();
    $livestockAnimalsQueryBuilder = $em->createQueryBuilder();

    //Base case, get animals of all gender types for livestock
    $livestockAnimalsGenderExpression = $livestockAnimalsQueryBuilder->expr()->orX(
      $livestockAnimalsQueryBuilder->expr()->eq('animal.gender', "'MALE'"),
      $livestockAnimalsQueryBuilder->expr()->eq('animal.gender', "'FEMALE'"),
      $livestockAnimalsQueryBuilder->expr()->eq('animal.gender', "'NEUTER'")
    );

    //A filter was given to filter livestock on a given gender type
    if($queryOnlyOnAnimalGenderType) {
      switch ($queryOnlyOnAnimalGenderType) {
        case Ewe::class:
          $livestockAnimalsGenderExpression = $livestockAnimalsQueryBuilder->expr()->eq('animal.gender', "'FEMALE'");
          $cacheId .= '_'.Ewe::getShortClassName();
          break;
        case Ram::class:
          $livestockAnimalsGenderExpression = $livestockAnimalsQueryBuilder->expr()->eq('animal.gender', "'MALE'");
          $cacheId .= '_'.Ram::getShortClassName();
          break;
        case Neuter::class:
          $livestockAnimalsGenderExpression = $livestockAnimalsQueryBuilder->expr()->eq('animal.gender', "'NEUTER'");
          $cacheId .= '_'.Neuter::getShortClassName();
          break;
        default:
          break;
      }
    }

    $livestockAnimalsQueryBuilder
      ->select('animal')
      ->from ('AppBundle:Animal', 'animal')
      ->where($livestockAnimalsQueryBuilder->expr()->andX(
        $livestockAnimalsQueryBuilder->expr()->andX(
          $livestockAnimalsQueryBuilder->expr()->eq('animal.isAlive', $isAlive),
          $livestockAnimalsGenderExpression, //apply gender filter
          $livestockAnimalsQueryBuilder->expr()->orX(
            $livestockAnimalsQueryBuilder->expr()->isNull('animal.transferState'),
            $livestockAnimalsQueryBuilder->expr()->neq('animal.transferState', "'TRANSFERRING'")
          )),
          $livestockAnimalsQueryBuilder->expr()->eq('animal.location', $location->getId())
        ));

    $query = $livestockAnimalsQueryBuilder->getQuery();

    if (self::USE_REDIS_CACHE) {
        return $cacheService->get($cacheId, $query);
    }

    return $query->getResult();
  }

  /**
   * /**
   * Returns historic animals EXCLUDING animals on current location
   *
   * @param Location $location
   * @param CacheService $cacheService
   * @param Ram | Ewe | Neuter $queryOnlyOnAnimalGenderType
   * @return array
   */
  public function getHistoricLiveStock(Location $location, $cacheService, $queryOnlyOnAnimalGenderType = null)
  {
    // Null check
    if(!($location instanceof Location)) {
      return [];
    } elseif (!is_int($location->getId())) {
      return [];
    }

    $cacheId = AnimalRepository::HISTORIC_LIVESTOCK_CACHE_ID ;
    $cacheId = $cacheId . $location->getId(); //. sha1($location->getId());
    $idCurrentLocation = $location->getId();

    $em = $this->getEntityManager();

    $genderFilterExpression = null;

    $livestockAnimalQueryBuilder = $em->createQueryBuilder();
    $historicAnimalsQueryBuilder = $em->createQueryBuilder();

    //Base case, get animals of all gender types for  livestock
    $livestockAnimalsGenderExpression = $livestockAnimalQueryBuilder->expr()->orX(
      $livestockAnimalQueryBuilder->expr()->eq('a.gender', "'MALE'"),
      $livestockAnimalQueryBuilder->expr()->eq('a.gender', "'FEMALE'"),
      $livestockAnimalQueryBuilder->expr()->eq('a.gender', "'NEUTER'")
    );

    //Base case, get animals of all gender types for historic livestock
    $historicAnimalsGenderExpression = $historicAnimalsQueryBuilder->expr()->orX(
      $historicAnimalsQueryBuilder->expr()->eq('a.gender', "'MALE'"),
      $historicAnimalsQueryBuilder->expr()->eq('a.gender', "'FEMALE'"),
      $historicAnimalsQueryBuilder->expr()->eq('a.gender', "'NEUTER'")
    );

    //A filter was given to filter historic livestock on a given gender type
    if($queryOnlyOnAnimalGenderType) {
      switch ($queryOnlyOnAnimalGenderType) {
        case Ewe::getClassName():
          $livestockAnimalsGenderExpression = $livestockAnimalQueryBuilder->expr()->eq('animal.gender', "'FEMALE'");
          $historicAnimalsGenderExpression = $historicAnimalsQueryBuilder->expr()->eq('a.gender', "'FEMALE'");
          $cacheId .= '_'.Ewe::getShortClassName();
          break;
        case Ram::getClassName():
          $livestockAnimalsGenderExpression = $livestockAnimalQueryBuilder->expr()->eq('animal.gender', "'MALE'");
          $historicAnimalsGenderExpression = $historicAnimalsQueryBuilder->expr()->eq('a.gender', "'MALE'");
          $cacheId .= '_'.Ram::getShortClassName();
          break;
        case Neuter::getClassName():
          $livestockAnimalsGenderExpression = $livestockAnimalQueryBuilder->expr()->eq('animal.gender', "'NEUTER'");
          $historicAnimalsGenderExpression = $historicAnimalsQueryBuilder->expr()->eq('a.gender', "'NEUTER'");
          $cacheId .= '_'.Neuter::getShortClassName();
          break;
        default:
          break;

      }
    }

    //Create currentLiveStock Query to use as subselect
    $livestockAnimalQueryBuilder
      ->select('animal')
      ->from ('AppBundle:Animal', 'animal')
      ->where($livestockAnimalQueryBuilder->expr()->andX(
        $livestockAnimalQueryBuilder->expr()->andX(
          $livestockAnimalQueryBuilder->expr()->eq('animal.isAlive','true'),
          $livestockAnimalQueryBuilder->expr()->orX(
            $livestockAnimalQueryBuilder->expr()->isNull('animal.transferState'),
            $livestockAnimalQueryBuilder->expr()->neq('animal.transferState', "'TRANSFERRING'")
          )
        ),
        $livestockAnimalQueryBuilder->expr()->eq('animal.location', $location->getId()),
        $livestockAnimalsGenderExpression
      ));
    $livestockAnimalQuery = $livestockAnimalQueryBuilder->getQuery();


    $livestockAnimalQuery->useQueryCache(true);
    $livestockAnimalQuery->setCacheable(true);
    $livestockAnimalQuery->useResultCache(true, Constant::CACHE_LIVESTOCK_TIME_SPAN, AnimalRepository::LIVESTOCK_CACHE_ID .$location->getId());

    //Create historicLivestock Query and use currentLivestock Query
    //as Subselect to get only Historic Livestock Animals
    $historicAnimalsQuery =
      $historicAnimalsQueryBuilder
        ->select('a,r')
        ->from('AppBundle:AnimalResidence', 'r')
        ->innerJoin('r.animal', 'a', Join::WITH, $historicAnimalsQueryBuilder->expr()->eq('r.animal', 'a.id'))
        ->leftJoin('r.location', 'l', Join::WITH, $historicAnimalsQueryBuilder->expr()->eq('a.location', 'l.id'))
        ->leftJoin('l.company', 'c', Join::WITH, $historicAnimalsQueryBuilder->expr()->eq('l.company', 'c.id'))
        ->where($historicAnimalsQueryBuilder->expr()->andX(
          $historicAnimalsQueryBuilder->expr()->eq('r.location', $idCurrentLocation),
          $historicAnimalsQueryBuilder->expr()->notIn('r.animal', $livestockAnimalQueryBuilder->getDQL()),
          $historicAnimalsGenderExpression //apply gender filter
        ));

    $query = $historicAnimalsQuery->getQuery();

    //Returns a list of AnimalResidences
    if (self::USE_REDIS_CACHE) {
        $retrievedHistoricAnimalResidences = $cacheService->get($cacheId, $query);
    } else {
        $retrievedHistoricAnimalResidences = $query->getResult();
    }

    $historicLivestock = [];

    //Grab the animals on returned residences
    /** @var AnimalResidence $historicAnimalResidence */
    foreach ($retrievedHistoricAnimalResidences as $historicAnimalResidence) {
      $historicLivestock[$historicAnimalResidence->getAnimal()->getId()] = $historicAnimalResidence->getAnimal();
    }

    return $historicLivestock;
  }

  /**
   * @param Client $client
   * @param string $ulnString
   * @return null|Animal
   */
  public function getAnimalByUlnString(Client $client, $uln)
  {
    $ulnCountryCode = null;
    $ulnNumber = null;

    if(!$uln instanceof ArrayCollection) {
      $uln = Utils::getUlnFromString($uln);
    }

    $ulnCountryCode = $uln[Constant::ULN_COUNTRY_CODE_NAMESPACE];
    $ulnNumber = $uln[Constant::ULN_NUMBER_NAMESPACE];

    if(!$ulnCountryCode && !$ulnNumber) {
      return null;
    }

    $locationIds = array();

    foreach ($client->getCompanies() as $company) {
      /** @var Location $location */
      foreach ($company->getLocations() as $location) {

        $locationIds[] = $location->getLocationId();
      }
    }

    $sql = "SELECT a.id, 
                   a.uln_country_code, 
                   a.uln_number, 
                   a.pedigree_country_code, 
                   a.pedigree_number, 
                   a.animal_order_number as work_number,
                   a.gender, 
                   a.date_of_birth, 
                   a.is_alive, 
                   a.is_departed_animal, 
                   c.last_weight as weight, 
                   c.weight_measurement_date      
              FROM animal a
              LEFT JOIN animal_cache c 
              ON a.id = c.animal_id
              WHERE a.is_alive = TRUE 
              AND (a.transfer_state ISNULL) 
              AND a.uln_number = '".$ulnNumber."'";

    $result = $this->getManager()->getConnection()->query($sql)->fetchAll();

    return $result;
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


  public function getAnimalByUlnOrPedigree($content)
  {
    $result = null;

    if (!is_array($content)) { return $result; }

    if(key_exists('uln_country_code', $content) && key_exists('uln_number', $content)) {
      if ($content['uln_country_code'] != '' && $content['uln_number'] != '') {
        $result = $this->findOneBy([
          'ulnCountryCode' => $content['uln_country_code'],
          'ulnNumber' => $content['uln_number'],
        ]);
      }

    } elseif (key_exists('pedigree_country_code', $content) && key_exists('pedigree_number', $content)) {
      if ($content['pedigree_country_code'] != '' && $content['pedigree_number'] != '') {
        $result = $this->findOneBy([
          'pedigreeCountryCode' => $content['pedigree_country_code'],
          'pedigreeNumber' => $content['pedigree_number'],
        ]);
      }
    }

    return $result;
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
          ->orderBy(['id' => Criteria::ASC]);

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
     * @param string $gender
     * @return string
     */
    private function getGenderJoinFilter($gender)
    {
        switch ($gender) {
            case GenderType::FEMALE: return ' INNER JOIN ewe ON ewe.id = a.id ';
            case GenderType::MALE: return ' INNER JOIN ram ON ram.id = a.id ';
            case GenderType::NEUTER: return ' INNER JOIN neuter ON neuter.id = a.id ';
            default: return '';
        }
    }


    /**
     * @param string $gender
     * @return ArrayCollection
     */
    public function getAnimalPrimaryKeysByVsmId($gender = null)
    {
        $sql = "SELECT a.id, a.name FROM animal a 
                  ".$this->getGenderJoinFilter($gender)."
                WHERE a.name IS NOT NULL";
        $results = $this->getConnection()->query($sql)->fetchAll();

        $array = new ArrayCollection();
        foreach ($results as $result) {
            $array->set($result['name'], intval($result['id']));
        }

        return $array;
    }


    /**
     * @param string $gender
     * @return array
     */
    public function getAnimalPrimaryKeysByVsmIdArray($gender = null)
    {
        $sql = "SELECT a.name as vsm_id, a.id FROM animal a
                  ".$this->getGenderJoinFilter($gender)."
                WHERE a.name IS NOT NULL";
        $results = $this->getConnection()->query($sql)->fetchAll();

        $searchArray = array();
        foreach ($results as $result) {
            $searchArray[$result['vsm_id']] = $result['id'];
        }
        return $searchArray;
    }


    /**
     * @param string $gender
     * @return array
     */
    public function getAnimalPrimaryKeysByUniqueStnArray($gender = null)
    {
        $sql = "SELECT a.id as animal_id, CONCAT(a.pedigree_country_code, a.pedigree_number) as stn
                FROM animal a
                INNER JOIN (
                    SELECT pedigree_country_code, pedigree_number
                    FROM animal a
                      ".$this->getGenderJoinFilter($gender)."
                    WHERE a.pedigree_country_code NOTNULL AND a.pedigree_number NOTNULL
                    --ignore duplicate stns
                    GROUP BY pedigree_country_code, pedigree_number HAVING COUNT(*) = 1
                    )g ON g.pedigree_country_code = a.pedigree_country_code AND g.pedigree_number = a.pedigree_number";
        $results = $this->getConnection()->query($sql)->fetchAll();

        $searchArray = array();
        foreach ($results as $result) {
            $searchArray[$result['stn']] = $result['animal_id'];
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
     * @param boolean $isCountryCodeSeparatedByString
     * @return array
     */
  private function getAnimalPrimaryKeysByUlnStringResults($isCountryCodeSeparatedByString)
  {
      if($isCountryCodeSeparatedByString) {
          $ulnFormat = "uln_country_code,' ',uln_number";
      } else {
          $ulnFormat = "uln_country_code,uln_number";
      }
      $sql = "SELECT CONCAT(".$ulnFormat.") as uln, id, type FROM animal";
      return $this->getConnection()->query($sql)->fetchAll();
  }


  /**
   * @return ArrayCollection
   */
  public function getAnimalPrimaryKeysByUlnString($isCountryCodeSeparatedByString = false)
  {
    $array = new ArrayCollection();
    foreach ($this->getAnimalPrimaryKeysByUlnStringResults($isCountryCodeSeparatedByString) as $result) {
      if($array->containsKey($array['uln'])) {
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
     * @return array
     */
    public function getAnimalPrimaryKeysByUlnStringArray($isCountryCodeSeparatedByString = false)
    {
        $array = [];
        foreach ($this->getAnimalPrimaryKeysByUlnStringResults($isCountryCodeSeparatedByString) as $result) {
            if(key_exists('uln', $array)) {
                if($result['type'] !== 'Neuter') {
                    $array[$result['uln']] = $result['id'];
                }
            } else {
                $array[$result['uln']] = $result['id'];
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
          JsonInputConstant::DATE_OF_BIRTH => TimeUtil::getDateTimeFromNullCheckedArrayValue('date_of_birth', $record, $replacementString),
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
    if($output != null) { $output->writeln('Find all testAnimals...'); }
    
    /** @var AnimalRepository $animalRepository */
    $animalRepository = $this->getManager()->getRepository(Animal::class);
    $testAnimals = $animalRepository->findBy(['ulnCountryCode' => 'XD']);

    $count = count($testAnimals);
    if($count == 0) {
      if($output != null) {
        $output->writeln('No testAnimals in Database');
      }
    } else {
      $counter = 0;
      if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt($count + 1, 1, 'Deleting testAnimals'); }
      foreach ($testAnimals as $testAnimal) {
        $this->getManager()->remove($testAnimal);
        if($cmdUtil != null) {
          $cmdUtil->advanceProgressBar(1);
        }
        $counter++;
        if($counter%self::BATCH == 0) { $this->getManager()->flush(); }
      }
      $this->getManager()->flush();
      if($cmdUtil != null) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }
    }
  }


  /**
   * @param string $ulnNumber
   * @param array $usedUlnNumbers
   * @return null|string
   */
  public function bumpUlnNumberWithVerification($ulnNumber, $usedUlnNumbers)
  {
      $newUlnNumber = StringUtil::bumpUlnNumber($ulnNumber);
      if($newUlnNumber == $ulnNumber) { return null; }
      if(array_key_exists($ulnNumber, $usedUlnNumbers)) { return null; }
      return $newUlnNumber;
  }


  /**
   * This information is necessary to show the most up to date information on the PedigreeCertificates
   *
   * @param CommandUtil $cmdUtil
   * @return int
   * @throws \Doctrine\DBAL\DBALException
   */
  public function updateAllLocationOfBirths(CommandUtil $cmdUtil = null)
  {
    $ubnsUpdated = 0;
    $updatedWithActiveLocations = 0;
    $updatedWithDeactivatedLocations = 0;
    
    /*
     * 1. Set current active locations on missing locationOfBirth where possible
     * 2. Set deactivated locations on missing locationOfBirth where possible
     */
    foreach (['TRUE', 'FALSE'] as $isActive) {
      $sql = "SELECT a.ubn_of_birth, l.id as location_id, l.is_active FROM animal a
              LEFT JOIN location l ON a.ubn_of_birth = l.ubn
            WHERE a.location_of_birth_id ISNULL AND l.id NOTNULL AND a.ubn_of_birth NOTNULL AND l.is_active = ".$isActive."
            GROUP BY ubn_of_birth, l.id, l.is_active";
      $results = $this->getConnection()->query($sql)->fetchAll();

      $internalCount = count($results);
      
      if($internalCount > 0) {
        if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt($internalCount,1); }

        foreach ($results as $result) {
          $ubnOfBirth = $result['ubn_of_birth'];
          $locationId = $result['location_id'];
          $sql = "UPDATE animal SET location_of_birth_id = ".$locationId." WHERE ubn_of_birth = '".$ubnOfBirth."'
                AND (location_of_birth_id <> ".$locationId." OR location_of_birth_id ISNULL)";
          $this->getConnection()->exec($sql);
          $ubnsUpdated++;

          if($isActive == 'TRUE') { $updatedWithActiveLocations++; }
          elseif($isActive == 'FALSE') { $updatedWithDeactivatedLocations++; }
          if($cmdUtil != null) { $cmdUtil->advanceProgressBar(1, 'LocationIdOfBirths updated, active|non-active: '
              .$updatedWithActiveLocations.'|'.$updatedWithDeactivatedLocations); }
        }
        if($cmdUtil != null) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }
      }

    }

    /*
     * 3. Do an extra check to see if any deactivated locations can be replaced by active locations
     */
    $sql = "SELECT a.ubn_of_birth, l.id as location_id FROM animal a
              LEFT JOIN location l ON a.ubn_of_birth = l.ubn
              LEFT JOIN location n ON n.id = a.location_of_birth_id
            WHERE a.location_of_birth_id ISNULL AND l.id NOTNULL AND a.ubn_of_birth NOTNULL AND l.is_active = TRUE
              AND n.is_active = FALSE
            GROUP BY ubn_of_birth, l.id";
    $results = $this->getConnection()->query($sql)->fetchAll();

    $internalCount = count($results);

    if($internalCount > 0) {
      if ($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt($internalCount, 1); }

      foreach ($results as $result) {
        $ubnOfBirth = $result['ubn_of_birth'];
        $locationId = $result['location_id'];
        $sql = "UPDATE animal SET location_of_birth_id = ".$locationId." WHERE ubn_of_birth = '".$ubnOfBirth."'
              AND location_of_birth_id <> ".$locationId;
        $this->getConnection()->exec($sql);
        $ubnsUpdated++;
        $updatedWithActiveLocations++;
        if($cmdUtil != null) { $cmdUtil->advanceProgressBar(1, 'LocationIdOfBirths updated, active|non-active: '
            .$updatedWithActiveLocations.'|'.$updatedWithDeactivatedLocations); }
      }
      if($cmdUtil != null) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }
    }

    return $ubnsUpdated;
  }


  /**
   * This information is necessary to show the most up to date information on the PedigreeCertificates
   *
   * @param Location $locationOfBirth
   */
  public function updateLocationOfBirth($locationOfBirth)
  {
    if($locationOfBirth instanceof Location) {
      $ubn = $locationOfBirth->getUbn();
      $id = $locationOfBirth->getId();
      if($locationOfBirth->getIsActive() && ctype_digit($ubn) && is_int($id)) {
        $sql = "UPDATE animal SET location_of_birth_id = ".$id." WHERE ubn_of_birth = '".$ubn."'";
        $this->getConnection()->exec($sql);
      }
    }
  }


  /**
   * This information is necessary to show the most up to date information on the PedigreeCertificates
   *
   * @param Company $company
   */
  public function updateLocationOfBirthByCompany(Company $company)
  {
    if($company instanceof Company) {
      /** @var Location $location */
      foreach($company->getLocations() as $location) {
        $this->updateLocationOfBirth($location);
      }
    }
  }


  /**
   * @param Connection $conn
   * @return int
   */
  public static function fixMissingAnimalTableExtentions(Connection $conn)
  {
    $sql = "SELECT a.id, 'Ewe' as type FROM animal a
            LEFT JOIN ewe e ON a.id = e.id
            WHERE a.type = 'Ewe' AND e.id ISNULL
            UNION
            SELECT a.id, 'Ram' as type FROM animal a
              LEFT JOIN ram r ON a.id = r.id
            WHERE a.type = 'Ram' AND r.id ISNULL
            UNION
            SELECT a.id, 'Neuter' as type FROM animal a
              LEFT JOIN neuter n ON a.id = n.id
            WHERE a.type = 'Neuter' AND n.id ISNULL";
    $results = $conn->query($sql)->fetchAll();

    $totalCount = count($results);

    if($totalCount > 0) {

      $eweAnimalIds = [];
      $ramAnimalIds = [];
      $neuterAnimalIds = [];
      foreach ($results as $result) {
        $animalId = $result['id'];
        $type = $result['type'];

        switch ($type) {
          case 'Ewe': $eweAnimalIds[$animalId] = $animalId; break;
          case 'Ram': $ramAnimalIds[$animalId] = $animalId; break;
          case 'Neuter': $neuterAnimalIds[$animalId] = $animalId; break;
        }
      }
      self::insertAnimalTableExtentions($conn, $eweAnimalIds, 'Ewe');
      self::insertAnimalTableExtentions($conn, $ramAnimalIds, 'Ram');
      self::insertAnimalTableExtentions($conn, $neuterAnimalIds, 'Neuter');
    }

    return $totalCount;
  }


  /**
   * @param Connection $conn
   * @param array $animalIds
   * @param string $type
   */
  private static function insertAnimalTableExtentions(Connection $conn, $animalIds, $type)
  {
    $batchSize = 1000;
    $tableName = strtolower($type);

    $counter = 0;
    $totalCount = count($animalIds);
    $valuesString = '';
    $animalIds = array_keys($animalIds);
    foreach ($animalIds as $animalId) {

      $valuesString = $valuesString."(" . $animalId . ", '".$type."')";
      $counter++;

      if($counter%$batchSize == 0) {
        $sql = "INSERT INTO ".$tableName." VALUES ".$valuesString;
        $conn->exec($sql);
        $valuesString = '';

      } elseif($counter != $totalCount) {
        $valuesString = $valuesString.',';
      }
    }
    if($valuesString != '') {
      $sql = "INSERT INTO ".$tableName." VALUES ".$valuesString;
      $conn->exec($sql);
    }
  }


  /**
   * @param OutputInterface|null $output
   * @return mixed
   * @throws \Doctrine\DBAL\DBALException
   */
  public function removePedigreeCountryCodeAndNumberIfPedigreeRegisterIsMissing(OutputInterface $output = null)
  {
    $sql = "SELECT COUNT(*) FROM animal a WHERE pedigree_number NOTNULL AND pedigree_register_id ISNULL";
    $count = $this->getConnection()->query($sql)->fetch()['count'];

    if($count == 0) {
      if($output != null) { $output->writeln('There are no animals with pedigreeNumbers and without a pedigreeRegister'); }
      return $count;
    }

    if($output != null) { $output->writeln('Clearing '.$count.' pedigreeNumbers for animals without a pedigreeRegister ...'); }
    
    $sql = "UPDATE animal SET pedigree_country_code = NULL, pedigree_number = NULL 
            WHERE pedigree_register_id ISNULL AND (pedigree_country_code NOTNULL OR pedigree_number NOTNULL)";
    $this->getConnection()->exec($sql);

    if($output != null) { $output->writeln($count.' pedigreeNumbers cleared for animals without a pedigreeRegister'); }

    return $count;
  }


  /**
   * @param string $type
   * @param int|string $animalId
   * @return boolean
   */
  public function deleteAnimalBySql($type, $animalId)
  {
    if(!is_string($type) || (!is_int($animalId) && !ctype_digit($animalId))) { return false; }
    if($type != 'Ewe' && $type != 'Ram' && $type != 'Neuter') { return false; }

    //Deleting animal

    $sql = "DELETE FROM ".strtolower($type)." WHERE id = ".$animalId;
    $this->getConnection()->exec($sql);

    $sql = "DELETE FROM animal WHERE id = ".$animalId;
    $this->getConnection()->exec($sql);

    return true;
  }





  /**
   * @param array|int $animalIds
   */
  public function deleteAnimalsById($animalIds)
  {
    if(!is_array($animalIds)) {

      if(ctype_digit($animalIds)) {
        $animalIds = intval($animalIds);
      }

      if(is_int($animalIds)) {
        $animalIds = [$animalIds];
      } else {
        return; 
      }
    }
    if(count($animalIds) == 0) { return; }

    //Delete animalCache records
    /** @var AnimalCacheRepository $animalCacheRepository */
    $animalCacheRepository = $this->getManager()->getRepository(AnimalCache::class);
    $animalCacheRepository->deleteByAnimalIdsAndSql($animalIds);

    //Delete resultTableBreedGrade record
    /** @var ResultTableBreedGradesRepository $resultTableBreedGradeRepository */
    $resultTableBreedGradeRepository = $this->getManager()->getRepository(ResultTableBreedGrades::class);
    $resultTableBreedGradeRepository->deleteByAnimalIdsAndSql($animalIds);

    //Delete animalResidence records
    /** @var AnimalResidenceRepository $animalResidenceRepository */
    $animalResidenceRepository = $this->getManager()->getRepository(AnimalResidence::class);
    $animalResidenceRepository->deleteByAnimalIdsAndSql($animalIds);

    /** @var GenderHistoryItemRepository $genderHistoryItemRepository */
    $genderHistoryItemRepository = $this->getManager()->getRepository(GenderHistoryItem::class);
    $genderHistoryItemRepository->deleteByAnimalsIds($animalIds);


    $animalIdFilterString = SqlUtil::getFilterStringByIdsArray($animalIds);
    if($animalIdFilterString != '') {
      //Note that the child record in the ram/ewe/neuter table will automatically be deleted as well.
      $sql = "DELETE FROM animal WHERE ".$animalIdFilterString;
      $this->getConnection()->exec($sql);
    }
  }


    /**
     * @param $ulnCountryCode
     * @param $ulnNumber
     * @param \DateTime $dateOfBirth
     * @return Animal|Ram|Ewe|Neuter|null
     */
  public function findOneByUlnAndDateOfBirth($ulnCountryCode, $ulnNumber, $dateOfBirth)
  {
      $qb = $this->getManager()->createQueryBuilder();
      $query =
          $qb
              ->select('animal')
              ->from(Animal::class, 'animal')
              ->where($qb->expr()->andX(
                  $qb->expr()->eq('animal.dateOfBirth', "'".($dateOfBirth->format('Y-m-d'))."'"),
                  $qb->expr()->eq('animal.ulnCountryCode', "'".$ulnCountryCode."'"),
                  $qb->expr()->eq('animal.ulnNumber', "'".$ulnNumber."'")
              ))
              ->orderBy('animal.id' ,'DESC')
              ->getQuery();

      $results = $query->getResult();
      if ($results > 0) {
          return ArrayUtil::firstValue($results);
      }
      return null;
  }

}
