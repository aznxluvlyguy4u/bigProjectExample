<?php

namespace AppBundle\Entity;

use AppBundle\Component\Count;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Criteria\AnimalCriteria;
use AppBundle\Criteria\MateCriteria;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Enumerator\AnimalTransferStatus;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Service\BaseSerializer;
use AppBundle\Service\CacheService;
use AppBundle\Util\AnimalArrayReader;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Connection;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;
use Doctrine\ORM\Query\Parameter;
use Symfony\Component\Console\Output\OutputInterface;
use Snc\RedisBundle\Client\Phpredis\Client as PredisClient;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AnimalRepository
 * @package AppBundle\Entity
 */
class AnimalRepository extends BaseRepository
{
  const BATCH = 1000;
  const USE_REDIS_CACHE = true; //TODO activate this when the livestock and historicLivestock redis cache is fixed
  const LIVESTOCK_CACHE_ID = 'GET_LIVESTOCK_';
  const EWES_LIVESTOCK_WITH_LAST_MATE_CACHE_ID = 'GET_EWES_LIVESTOCK_WITH_LAST_MATE_';
  const HISTORIC_LIVESTOCK_CACHE_ID = 'GET_HISTORIC_LIVESTOCK_';
  const CANDIDATE_FATHERS_CACHE_ID = 'GET_CANDIDATE_FATHERS_';
  const CANDIDATE_MOTHERS_CACHE_ID = 'GET_CANDIDATE_MOTHERS_';
  const CANDIDATE_SURROGATES_CACHE_ID = 'GET_CANDIDATE_SURROGATES_';
  const ANIMAL_ALIAS = 'animal';

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
     * @param BaseSerializer $serializer
     * @param bool $onlyIncludeAliveEwes
     * @return array
     */
  public function getEwesLivestockWithLastMate(Location $location,
                                               CacheService $cacheService,
                                               BaseSerializer $serializer,
                                               $onlyIncludeAliveEwes = true)
  {
      $clazz = Ewe::class;

      //Returns a list of AnimalResidences
      if (self::USE_REDIS_CACHE) {
          $cacheId = self::getEwesLivestockWithLastMateCacheId($location);

          if ($cacheService->isHit($cacheId)) {
              $ewes = $serializer->deserializeArrayOfObjects($cacheService->getItem($cacheId), $clazz);
          } else {
              /** @var Ewe[] $ewes */
              $ewes = $this->getEwesLivestockWithLastMateQuery($location, $onlyIncludeAliveEwes)->getResult();

              foreach ($ewes as $ewe) {
                  $ewe->onlyKeepLastActiveMateInMatings();
              }

              $jmsGroups = self::getEwesLivestockWithLastMateJmsGroups();
              $jmsGroups[] = JmsGroup::MATINGS;

              $serializedEwes = $serializer->getArrayOfSerializedObjects($ewes, $jmsGroups,true);
              $ewes = $serializer->deserializeArrayOfObjects($serializedEwes, $clazz);

              $cacheService->set($cacheId, $serializedEwes);
          }

      } else {
          $ewes = $this->getEwesLivestockWithLastMateQuery($location, $onlyIncludeAliveEwes)->getResult();
      }

      return $ewes;
  }


    /**
     * @param Location $location
     * @param bool $onlyIncludeAliveEwes
     * @return \Doctrine\ORM\Query|string
     */
  private function getEwesLivestockWithLastMateQuery(Location $location,
                                                     $onlyIncludeAliveEwes = true)
  {
      $clazz = Ewe::class;
      $isAlive = $onlyIncludeAliveEwes ? null : true;

      $query = $this->getLivestockQuery($location, $isAlive, $clazz, false);
      $query->setFetchMode(Mate::class, 'studEwe', ClassMetadata::FETCH_EAGER);
      $query->setFetchMode(Ram::class, 'parentFather', ClassMetadata::FETCH_EXTRA_LAZY);
      $query->setFetchMode(Ewe::class, 'parentMother', ClassMetadata::FETCH_EXTRA_LAZY);
      $query->setFetchMode(Litter::class, 'litter', ClassMetadata::FETCH_EAGER);

      return $query;
  }


    /**
     * @return array
     */
    public static function getEwesLivestockWithLastMateJmsGroups()
    {
        return [JmsGroup::LIVESTOCK, JmsGroup::LAST_MATE];
    }


    /**
     * @param Location $location
     * @param CacheService $cacheService
     * @return boolean
     */
    public static function purgeEwesLivestockWithLastMateCache(Location $location, CacheService $cacheService)
    {
        if ($location) {
            return $cacheService->delete(self::getEwesLivestockWithLastMateCacheId($location));
        }
        return false;
    }


    /**
     * @param Location $location
     * @return string
     */
    public static function getEwesLivestockWithLastMateCacheId(Location $location)
    {
        return
            AnimalRepository::EWES_LIVESTOCK_WITH_LAST_MATE_CACHE_ID .
            $location->getId()
            ;
    }


  /**
   * @param Location $location
   * @param CacheService $cacheService
   * @param BaseSerializer $serializer
   * @param bool $isAlive
   * @param Animal $queryOnlyOnAnimalGenderType
   * @param array $extraJmsGroups
   *
   * @return array
   */
  public function getLiveStock(Location $location,
                               CacheService $cacheService,
                               BaseSerializer $serializer,
                               $isAlive = true,
                               $queryOnlyOnAnimalGenderType = null,
                               array $extraJmsGroups = []
  )
  {
    $cacheId = $this->getLivestockCacheId($location, $queryOnlyOnAnimalGenderType, $extraJmsGroups);
    $query = $this->getLivestockQuery($location, $isAlive, $queryOnlyOnAnimalGenderType, false);

    $clazz = $queryOnlyOnAnimalGenderType === null ? Animal::class : $queryOnlyOnAnimalGenderType;

    //Returns a list of AnimalResidences
    if (self::USE_REDIS_CACHE) {
        if ($cacheService->isHit($cacheId)) {
            $animals = $serializer->deserializeArrayOfObjects($cacheService->getItem($cacheId), $clazz);
        } else {
            $animals = $query->getResult();

            $standardJmsGroups = [JmsGroup::BASIC, JmsGroup::LIVESTOCK];
            $jmsGroups = count($extraJmsGroups) > 0 ? ArrayUtil::concatArrayValues([$extraJmsGroups, $standardJmsGroups], true): $standardJmsGroups;

            $serializedAnimals = $serializer->getArrayOfSerializedObjects($animals, $jmsGroups,true);
            $cacheService->set($cacheId, $serializedAnimals);
        }

    } else {
        $animals = $query->getResult();
    }

    return $animals;
  }


    /**
     * @param Location $location
     * @param bool $isAlive set to null to ignore isAlive status
     * @param string $queryOnlyOnAnimalGenderType
     * @param bool $ignoreTransferState
     * @return QueryBuilder
     */
  public function getLivestockQueryBuilder(Location $location, $isAlive = true, $queryOnlyOnAnimalGenderType = null,
                                           $ignoreTransferState = false)
  {
      $livestockAnimalsQueryBuilder = $this->getManager()->createQueryBuilder();

      $isAliveFilter = null;
      if ($isAlive !== null) {
          $isAlive = $isAlive ? 'true' : 'false';
          $isAliveFilter = $livestockAnimalsQueryBuilder->expr()->eq('animal.isAlive', $isAlive);
      }

      $livestockAnimalsQueryBuilder
          ->select('animal')
          ->from (Animal::class, 'animal')
          ->where($livestockAnimalsQueryBuilder->expr()->andX(
              $livestockAnimalsQueryBuilder->expr()->andX(
                  $isAliveFilter,
                  $this->getLiveStockQueryGenderFilter($livestockAnimalsQueryBuilder, $queryOnlyOnAnimalGenderType, 'animal'), //apply gender filter
                  $this->getLivestockQueryTransferStateFilter($livestockAnimalsQueryBuilder, $ignoreTransferState)
              ),
              $livestockAnimalsQueryBuilder->expr()->eq('animal.location', $location->getId())
          ));

      return $livestockAnimalsQueryBuilder;
  }


    /**
     * @param Location $location
     * @param bool $isAlive
     * @param string $queryOnlyOnAnimalGenderType
     * @param boolean $returnDQL
     * @param boolean $ignoreTransferState
     * @return \Doctrine\ORM\Query | string
     */
  private function getLivestockQuery(Location $location, $isAlive = true, $queryOnlyOnAnimalGenderType = null,
                                     $returnDQL = false, $ignoreTransferState = false)
  {
      $livestockAnimalsQueryBuilder = $this->getLivestockQueryBuilder($location, $isAlive, $queryOnlyOnAnimalGenderType, $ignoreTransferState);

      $livestockAnimalQuery = $livestockAnimalsQueryBuilder->getQuery();

      $livestockAnimalQuery->useQueryCache(true);
      $livestockAnimalQuery->setCacheable(true);
      // DO NOT use $livestockAnimalQuery->useResultCache(), use the serialized results in the redis cache instead!

      if ($returnDQL) {
          return $livestockAnimalsQueryBuilder->getDQL();
      }

      return $livestockAnimalQuery;
  }


    /**
     * @param QueryBuilder $livestockAnimalsQueryBuilder
     * @param string $queryOnlyOnAnimalGenderType
     * @param string $alias
     * @return \Doctrine\DBAL\Query\Expression\CompositeExpression|string
     */
  private function getLiveStockQueryGenderFilter(QueryBuilder $livestockAnimalsQueryBuilder, $queryOnlyOnAnimalGenderType, $alias = 'animal')
  {
      $maleQueryFilter = $livestockAnimalsQueryBuilder->expr()->eq($alias.'.gender', "'".GenderType::MALE."'");
      $femaleQueryFilter = $livestockAnimalsQueryBuilder->expr()->eq($alias.'.gender', "'".GenderType::FEMALE."'");
      $neuterQueryFilter = $livestockAnimalsQueryBuilder->expr()->eq($alias.'.gender', "'".GenderType::NEUTER."'");

      //A filter was given to filter livestock on a given gender type
      if($queryOnlyOnAnimalGenderType) {
          switch ($queryOnlyOnAnimalGenderType) {
              case Ram::class: return $maleQueryFilter;
              case Ewe::class: return $femaleQueryFilter;
              case Neuter::class: return $neuterQueryFilter;
              default: break;
          }
      }

      //Base case, get animals of all gender types for livestock
      return $livestockAnimalsQueryBuilder->expr()->orX(
          $maleQueryFilter,
          $femaleQueryFilter,
          $neuterQueryFilter
      );
  }


    /**
     * @param QueryBuilder $livestockAnimalsQueryBuilder
     * @param bool $ignoreTransferState
     * @return Expr\Orx|null
     */
  private function getLivestockQueryTransferStateFilter(QueryBuilder $livestockAnimalsQueryBuilder, $ignoreTransferState = false)
  {
      if ($ignoreTransferState) {
          return null;
      }

      return $livestockAnimalsQueryBuilder->expr()->orX(
          $livestockAnimalsQueryBuilder->expr()->isNull('animal.transferState'),
          $livestockAnimalsQueryBuilder->expr()->neq('animal.transferState', "'".AnimalTransferStatus::TRANSFERRING."'")
      );
  }


    /**
     * @param Location $location
     * @param string $queryOnlyOnAnimalGenderType
     * @param array $extraJmsGroups
     * @return string
     */
    private function getLivestockCacheId(Location $location, $queryOnlyOnAnimalGenderType = null, $extraJmsGroups = [])
    {
        return
            AnimalRepository::LIVESTOCK_CACHE_ID .
            $location->getId() .
            $this->getGenderSuffix($queryOnlyOnAnimalGenderType) .
            CacheService::getJmsGroupsSuffix($extraJmsGroups)
        ;
    }


    /**
     * @param Location $location
     * @param string $queryOnlyOnAnimalGenderType
     * @param array $extraJmsGroups
     * @return string
     */
    private function getHistoricLivestockCacheId(Location $location, $queryOnlyOnAnimalGenderType = null, $extraJmsGroups = [])
    {
        return
            AnimalRepository::HISTORIC_LIVESTOCK_CACHE_ID .
            $location->getId() .
            $this->getGenderSuffix($queryOnlyOnAnimalGenderType) .
            CacheService::getJmsGroupsSuffix($extraJmsGroups)
        ;
    }


    /**
     * @param string $queryOnlyOnAnimalGenderType
     * @return string
     */
    private function getGenderSuffix($queryOnlyOnAnimalGenderType = null)
    {
        //A filter was given to filter livestock on a given gender type
        if($queryOnlyOnAnimalGenderType) {
          switch ($queryOnlyOnAnimalGenderType) {
              case Ewe::class:      return '_'.Ewe::getShortClassName();
              case Ram::class:      return '_'.Ram::getShortClassName();
              case Neuter::class:   return '_'.Neuter::getShortClassName();
              default: break;
          }
        }
        return '';
    }


  /**
   * /**
   * Returns historic animals EXCLUDING animals on current location
   *
   * @param Location $location
   * @param CacheService $cacheService
   * @param BaseSerializer $serializer
   * @param Ram | Ewe | Neuter $queryOnlyOnAnimalGenderType
   * @return array
   */
  public function getHistoricLiveStock(Location $location, $cacheService, $serializer, $queryOnlyOnAnimalGenderType = null)
  {
    // Null check
    if(!($location instanceof Location)) {
      return [];
    } elseif (!is_int($location->getId())) {
      return [];
    }

    $cacheId = $this->getHistoricLivestockCacheId($location, $queryOnlyOnAnimalGenderType);

    $historicAnimalsQueryBuilder = $this->getManager()->createQueryBuilder();

    //Create currentLiveStock Query to use as subselect
    $livestockAnimalDQLQuery = $this->getLivestockQuery($location, true, $queryOnlyOnAnimalGenderType, true);

    //Create historicLivestock Query and use currentLivestock Query
    //as Subselect to get only Historic Livestock Animals
    $historicAnimalsQuery =
      $historicAnimalsQueryBuilder
        ->select('a,r,l')
        ->from(AnimalResidence::class, 'r')
        ->innerJoin('r.animal', 'a', Join::WITH, $historicAnimalsQueryBuilder->expr()->eq('r.animal', 'a.id'))
        ->leftJoin('r.location', 'l', Join::WITH, $historicAnimalsQueryBuilder->expr()->eq('a.location', 'l.id'))
        ->leftJoin('l.company', 'c', Join::WITH, $historicAnimalsQueryBuilder->expr()->eq('l.company', 'c.id'))
        ->where($historicAnimalsQueryBuilder->expr()->andX(
          $historicAnimalsQueryBuilder->expr()->eq('r.location', $location->getId()),
          $historicAnimalsQueryBuilder->expr()->notIn('r.animal', $livestockAnimalDQLQuery),
            $this->getLiveStockQueryGenderFilter($historicAnimalsQueryBuilder, $queryOnlyOnAnimalGenderType, 'a') //apply gender filter
        ));

    $query = $historicAnimalsQuery->getQuery();
    $query->setFetchMode(AnimalResidence::class, 'animal', ClassMetadata::FETCH_EAGER);
    $query->setFetchMode(Animal::class, 'location', ClassMetadata::FETCH_EAGER);

    //Returns a list of AnimalResidences
    if (self::USE_REDIS_CACHE) {
        if ($cacheService->isHit($cacheId)) {
            $historicLivestock = $serializer->deserializeArrayOfObjects($cacheService->getItem($cacheId), Animal::class);
        } else {
            $retrievedHistoricAnimalResidences = $query->getResult();
            $historicLivestock = $this->getHistoricLivestockFromResidences($retrievedHistoricAnimalResidences);

            $serializedHistoricLivestock = $serializer->getArrayOfSerializedObjects($historicLivestock, [JmsGroup::BASIC, JmsGroup::LIVESTOCK],true);
            $cacheService->set($cacheId, $serializedHistoricLivestock);
        }

    } else {
        $retrievedHistoricAnimalResidences = $query->getResult();
        $historicLivestock = $this->getHistoricLivestockFromResidences($retrievedHistoricAnimalResidences);
    }

    return $historicLivestock;
  }


    /**
     * @param $retrievedHistoricAnimalResidences
     * @return array
     */
  private function getHistoricLivestockFromResidences($retrievedHistoricAnimalResidences)
  {
      $historicLivestock = [];

      //Grab the animals on returned residences
      /** @var AnimalResidence $historicAnimalResidence */
      foreach ($retrievedHistoricAnimalResidences as $historicAnimalResidence)
      {
          $animalId = $historicAnimalResidence->getAnimal()->getId();
          if (!key_exists($animalId, $historicLivestock)) {
              $historicLivestock[$animalId] = $historicAnimalResidence->getAnimal();
          }
      }
      return $historicLivestock;
  }



  public function getCandidateMothersForBirth(Location $location,
                                              CacheService $cacheService,
                                              BaseSerializer $serializer,
                                              $onlyIncludeAliveEwes = false
  )
  {
      $clazz = Ewe::class;

      //Create currentLiveStock Query to use as subselect
      $isAlive = $onlyIncludeAliveEwes ? true : null;
      $livestockAnimalDQLQuery = $this->getLivestockQuery(
          $location,
          $isAlive,
          $clazz,
          true,
          true
      );

      $mateQb = $this->getManager()->createQueryBuilder();

      $mateQb
          ->select('mate')
          ->from(Mate::class, 'mate')
          ->leftJoin('mate.litter', 'litter')
          ->where($mateQb->expr()->eq('mate.requestState', "'".RequestStateType::FINISHED."'"))
          ->andWhere($mateQb->expr()->in('mate.studEwe', $livestockAnimalDQLQuery))
          ->andWhere($mateQb->expr()->eq('mate.location', $location->getId()))
          ->andWhere(
              $mateQb->expr()->isNull('litter.id')
          )
      ;

      $query = $mateQb->getQuery();
      $query->setFetchMode(Mate::class, 'studEwe', ClassMetadata::FETCH_EAGER);
      $query->setFetchMode(Animal::class, 'location', ClassMetadata::FETCH_EAGER);


      //Returns a list of AnimalResidences
      if (self::USE_REDIS_CACHE) {
          $cacheId = $this->getCandidateMothersCacheId($location);

          if ($cacheService->isHit($cacheId)) {
              $studEwes = $serializer->deserializeArrayOfObjects($cacheService->getItem($cacheId), $clazz);
          } else {
              $mates = $query->getResult();
              $studEwes = $this->getEwesFromMates($mates);

              $jmsGroups = [JmsGroup::BASIC, JmsGroup::LIVESTOCK, JmsGroup::MATINGS];

              $serializedStudEwes = $serializer->getArrayOfSerializedObjects($studEwes, $jmsGroups,true);
              $cacheService->set($cacheId, $serializedStudEwes);
          }

      } else {
          $mates = $query->getResult();
          $studEwes = $this->getEwesFromMates($mates);
      }

      return $studEwes;
  }


    /**
     * @param Mate[] $mates
     * @return Ewe[]
     */
  private function getEwesFromMates($mates)
  {
      $studEwes = [];

      //Grab the animals on returned residences
      foreach ($mates as $mate)
      {
          $studEwe = $mate->getStudEwe();
          if ($studEwe === null) {
              continue;
          }

          $animalId = $studEwe->getId();
          if (!key_exists($animalId, $studEwes)) {
              $studEwes[$animalId] = $studEwe;
          }
      }
      return $studEwes;
  }


    /**
     * @param Location $location
     * @param CacheService $cacheService
     * @return boolean
     */
  public function purgeCandidateMothersCache(Location $location, CacheService $cacheService)
  {
      if ($location) {
          return $cacheService->delete($this->getCandidateMothersCacheId($location));
      }
      return false;
  }


    /**
     * @param Location $location
     * @return string
     */
  private function getCandidateMothersCacheId(Location $location)
  {
      return
          AnimalRepository::CANDIDATE_MOTHERS_CACHE_ID .
          $location->getId()
      ;
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


    /**
     * @param string $ulnString
     * @return Animal[]|Ram[]|array
     */
    public function findAnimalsByUlnString($ulnString)
    {
        $ulnParts = Utils::getUlnFromString($ulnString);

        if ($ulnParts === null) {
            return [];
        }

        return $this->findBy(
            [
                'ulnCountryCode' => $ulnParts[Constant::ULN_COUNTRY_CODE_NAMESPACE],
                'ulnNumber' => $ulnParts[Constant::ULN_NUMBER_NAMESPACE],
            ]);
    }


    /**
     * @param string $stnString
     * @return Animal[]|Ram[]|array
     */
    public function findAnimalsByStnString($stnString)
    {
        $stnParts = Utils::getStnFromString($stnString);

        if ($stnParts === null) {
            return [];
        }

        return $this->findBy(
            [
                'pedigreeCountryCode' => $stnParts[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE],
                'pedigreeNumber' => $stnParts[Constant::PEDIGREE_NUMBER_NAMESPACE],
            ]);
    }


    /**
     * @param string $ulnOrStnString
     * @param bool $includeInputType
     * @return Animal[]|Ram[]|array
     */
    public function findAnimalsByUlnOrStnString($ulnOrStnString, $includeInputType = false)
    {
        $ulnOrStnString = StringUtil::removeSpaces($ulnOrStnString);

        $animals = [];
        $inputType = ReportLabel::INVALID;

        if (Validator::verifyUlnFormat($ulnOrStnString, false)) {
            $animals = $this->findAnimalsByUlnString($ulnOrStnString);
            $inputType = ReportLabel::ULN;

        } elseif (Validator::verifyPedigreeCountryCodeAndNumberFormat($ulnOrStnString, false)) {
            $animals = $this->findAnimalsByStnString($ulnOrStnString);
            $inputType = ReportLabel::STN;
        }

        if ($includeInputType) {
            return [
                JsonInputConstant::ANIMALS => $animals,
                JsonInputConstant::TYPE => $inputType,
            ];
        }

        return $animals;
    }


    /**
     * @param array $ids
     * @param bool $useIdAsKey
     * @param bool $onlyReturnQuery
     * @return array|\Doctrine\ORM\Query
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function findByIds(array $ids = [], $useIdAsKey = false, $onlyReturnQuery = false)
    {
        $qb = $this->createQueryBuilder(self::ANIMAL_ALIAS)->addCriteria(AnimalCriteria::byIds($ids));

        if ($useIdAsKey && $onlyReturnQuery === false) {
             $animals = $this->returnQueryOrResult($qb, false);
             $resultWithIdAsKeys = [];
             /**
              * @var int $key
              * @var Animal $animal
              */
            foreach ($animals as $key => $animal) {
                 $resultWithIdAsKeys[$animal->getId()] = $animal;
            }
            $animals = null;
            return $resultWithIdAsKeys;
        }

        return $this->returnQueryOrResult($qb, $onlyReturnQuery);
    }


    /**
     * @param array $ulnPartsArray
     * @param array $stnPartsArray
     * @param array $ubns
     * @param bool $onlyReturnQuery
     * @return array|\Doctrine\ORM\Query
     * @throws \Doctrine\ORM\Query\QueryException
     * @throws \Exception
     */
    public function findAnimalsByUlnPartsOrStnPartsOrUbns(array $ulnPartsArray = [], array $stnPartsArray = [],
                                                          array $ubns = [], $onlyReturnQuery = false)
    {
        if (count($ulnPartsArray) === 0 && count($stnPartsArray) === 0 && count($ubns) === 0) {
            if ($onlyReturnQuery = true) {
                return [];
            } else {
                // TODO return a proper empty result is only query is requested
                return null;
            }
        }

        $qb = $this->createQueryBuilder(self::ANIMAL_ALIAS)
            ->addCriteria(AnimalCriteria::byUlnOrStnParts($ulnPartsArray, $stnPartsArray, self::ANIMAL_ALIAS))
        ;

        if (count($ubns) > 0) {
            $locationsQuery = $this->getManager()->getRepository(Location::class)->getLocationsQueryByUbns($ubns);

            if ($locationsQuery !== null) {
                $qb->orWhere($qb->expr()->in('animal.location', $locationsQuery->getDQL()));

                /** @var Parameter $parameter */
                foreach ($locationsQuery->getParameters() as $parameter) {
                    $qb->setParameter($parameter->getName(), $parameter->getValue(), $parameter->getType());
                }
            }
        }

        return $this->returnQueryOrResult($qb, $onlyReturnQuery);
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
    public function getAnimalPrimaryKeysByUlnStringArrayIncludingTagReplaces($isCountryCodeSeparatedByString = false)
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

        return $this->includeAnimalIdsByUlnsFromDeclareTagReplacesToSearchArray($array);
    }


    private function includeAnimalIdsByUlnsFromDeclareTagReplacesToSearchArray(array $animalIdsByUlnArray)
    {
        $animalIdsByUlnArrayFromTagReplaces = $this->getManager()->getRepository(DeclareTagReplace::class)->getAnimalIdsByUlns();
        return ArrayUtil::concatArrayValues([$animalIdsByUlnArray, $animalIdsByUlnArrayFromTagReplaces],false);
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


    /**
     * @param array $ubns
     * @param array $ulns
     * @return array
     */
  public function findByUbnsOrUlns(array $ubns = [], $ulns = [])
  {
      $qb = $this->getManager()->createQueryBuilder();

      $qb
          ->select('animal')
          ->from(Animal::class, 'animal');

      $count = 1;
      foreach ($ulns as $ulnData) {
          $ulnCountryCode = ArrayUtil::get(JsonInputConstant::ULN_COUNTRY_CODE, $ulnData);
          $ulnNumber = ArrayUtil::get(JsonInputConstant::ULN_NUMBER, $ulnData);

          $countryCodeParameter = 'ulnCountryCode'.$count;
          $numberParameter = 'ulnNumber'.$count;

          $ulnQuery = $qb->expr()->andX(
              $qb->expr()->eq('animal.ulnCountryCode', ':'.$countryCodeParameter),
              $qb->expr()->eq('animal.ulnNumber', ':'.$numberParameter)
          );

          $qb
              ->orWhere($ulnQuery)
              ->setParameter($countryCodeParameter, $ulnCountryCode, Type::STRING)
              ->setParameter($numberParameter, $ulnNumber, Type::STRING)
          ;

          $count++;
      }

      $locationsQuery = $this->getManager()->getRepository(Location::class)->getLocationsQueryByUbns($ubns);

      if ($locationsQuery !== null) {
          $qb->orWhere($qb->expr()->in('animal.location', $locationsQuery->getDQL()));

          /** @var Parameter $parameter */
          foreach ($locationsQuery->getParameters() as $parameter) {
              $qb->setParameter($parameter->getName(), $parameter->getValue(), $parameter->getType());
          }
      }

      $qb
          ->orderBy('animal.ulnCountryCode' ,'ASC')
          ->addOrderBy('animal.ulnNumber', 'ASC');

      $query = $qb->getQuery();

      return $query->getResult();
  }


    /**
     * @param string|int $id
     * @return Animal|Ewe|Neuter|Ram|null
     */
    public function findAnimalByIdOrUln($id)
    {
        if(StringUtil::isStringContains($id, 'NL')) {
            return $this->findAnimalByUlnString($id);
        } elseif(ctype_digit($id) || is_int($id)) {
            return $this->find($id);
        }
        return null;
    }


    /**
     * @param array|string $ulns
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function ulnCounts($ulns)
    {
        if (is_string($ulns)) {
            $ulns = [$ulns];
        } elseif (!is_array($ulns)) {
            throw new \Exception('Input should be a uln string or array of uln strings');
        }

        if (count($ulns) === 0) {
            throw new \Exception('uln string is missing');
        }

        $ulnsString = "'" . implode("','", $ulns) . "'";

        $sql = "SELECT COUNT(*) as count, CONCAT(uln_country_code, uln_number) as uln
                FROM animal
                WHERE CONCAT(uln_country_code, uln_number) IN (
                  ".$ulnsString."
                )
                GROUP BY CONCAT(uln_country_code, uln_number)";
        $results = $this->getConnection()->query($sql)->fetchAll();

        $counts = [];
        foreach ($results as $result) {
            $count = $result['count'];
            $uln = $result['uln'];
            $counts[$uln] = $count;
        }

        foreach ($ulns as $uln) {
            if (!key_exists($uln, $counts)) {
                $counts[$uln] = 0;
            }
        }

        return $counts;
    }


    /**
     * @param array $ulns
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getDuplicateCountsByUln(array $ulns = [])
    {
        if (count($ulns) === 0) {
            return [];
        }

        $sql = "SELECT COUNT(*) as count, CONCAT(uln_country_code, uln_number) as uln
                FROM animal
                  WHERE CONCAT(uln_country_code, uln_number) IN (".SqlUtil::getFilterListString($ulns, true).") 
                GROUP BY CONCAT(uln_country_code, uln_number) HAVING COUNT(*) > 1";
        $results = $this->getConnection()->query($sql)->fetchAll();
        return SqlUtil::groupSqlResultsOfKey1ByKey2('count', 'uln', $results,true, false);
    }


    /**
     * @param array $stns
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getDuplicateCountsByStn(array $stns = [])
    {
        if (count($stns) === 0) {
            return [];
        }

        $sql = "SELECT COUNT(*) as count, CONCAT(pedigree_country_code, pedigree_number) as stn
                FROM animal
                  WHERE CONCAT(pedigree_country_code, pedigree_number) IN (".SqlUtil::getFilterListString($stns, true).") 
                GROUP BY CONCAT(pedigree_country_code, pedigree_number) HAVING COUNT(*) > 1";
        $results = $this->getConnection()->query($sql)->fetchAll();
        return SqlUtil::groupSqlResultsOfKey1ByKey2('count', 'stn', $results,true, false);
    }


    /**
     * @param boolean $onlyIncludeCurrentLivestockAnimals
     * @param int $minAnimalId
     * @return array|Animal[]
     */
    public function getAllAnimalsFromDeclareBirth($onlyIncludeCurrentLivestockAnimals, $minAnimalId = 0)
    {
        $qb = $this->getManager()->createQueryBuilder();

        $notNullLocationQuery = null;
        $animalIsAliveQuery = null;

        if ($onlyIncludeCurrentLivestockAnimals) {
            $notNullLocationQuery = $qb->expr()->isNotNull('animal.location');
            $animalIsAliveQuery = $qb->expr()->eq('animal.isAlive', 'true');
        }

        $qb
            ->select('b', 'animal')
            ->from(DeclareBirth::class, 'b')
            ->innerJoin('b.animal', 'animal', Join::WITH, $qb->expr()->eq('b.animal', 'animal.id'))
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->eq('b.requestState', "'".RequestStateType::FINISHED."'"),
                        $qb->expr()->eq('b.requestState', "'".RequestStateType::FINISHED_WITH_WARNING."'")
                    ),
                    $qb->expr()->gte('animal.id', $minAnimalId),
                    $notNullLocationQuery,
                    $animalIsAliveQuery
                )
            )
            ->orderBy('b.dateOfBirth', Criteria::ASC)
        ;

        $animals = [];

        /** @var DeclareBirth $declareBirth */
        foreach ($qb->getQuery()->getResult() as $declareBirth)
        {
            $animal = $declareBirth->getAnimal();
            if (!$animal) {
                continue;
            }

            if (!key_exists($animal->getId(), $animals)) {
                $animals[$animal->getId()] = $animal;
            }
        }

        return $animals;
    }


    /**
     * @param array $animalsArray
     * @return array
     * @throws \Exception
     */
    public function getAnimalIdsFromAnimalsArray($animalsArray = [])
    {
        if (count($animalsArray) === 0) {
            return [];
        }

        Validator::validateUlnKeysInArray($animalsArray, true);

        $sql = '';
        $prefix = 'SELECT a.id FROM animal a WHERE ';
        foreach ($animalsArray as $animalData)
        {
            $ulnCountryCode = $animalData['uln_country_code'];
            $ulnNumber = $animalData['uln_number'];
            $sql .= $prefix."(a.uln_country_code = '$ulnCountryCode' AND a.uln_number = '$ulnNumber')";
            $prefix = ' OR ';
        }

        return SqlUtil::getSingleValueGroupedSqlResults('id', $this->getConnection()->query($sql)->fetchAll(), true);
    }

    /**
     * @param array $animalsArray
     * @param int $locationId
     * @return array
     * @throws \Exception
     */
    public function getCurrentAndHistoricAnimalIdsFromAnimalsArray($animalsArray = [], $locationId)
    {
        if (count($animalsArray) === 0) {
            return [];
        }

        if (!is_int($locationId) && !ctype_digit($locationId)) {
            throw new \Exception('Location id is missing', Response::HTTP_PRECONDITION_REQUIRED);
        }

        Validator::validateUlnKeysInArray($animalsArray, true);

        $sql = '';
        $prefix = "SELECT
                      a.id,
                      CONCAT(a.uln_country_code, a.uln_number) as uln,
                      COALESCE(a.location_id = $locationId OR r.animal_id NOTNULL, FALSE) as is_historic_livestock_animal
                    FROM animal a
                    LEFT JOIN (
                        SELECT
                          animal_id
                        FROM animal_residence
                        WHERE location_id = $locationId
                        GROUP BY animal_id
                        )r ON r.animal_id = a.id
                        WHERE ";

        foreach ($animalsArray as $animalData)
        {
            $ulnCountryCode = $animalData['uln_country_code'];
            $ulnNumber = $animalData['uln_number'];
            $sql .= $prefix."(a.uln_country_code = '$ulnCountryCode' AND a.uln_number = '$ulnNumber')";
            $prefix = ' OR ';
        }

        return $this->getConnection()->query($sql)->fetchAll();
    }
}
