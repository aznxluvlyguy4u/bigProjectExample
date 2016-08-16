<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Enumerator\AnimalTransferStatus;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Enumerator\LiveStockType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

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
    $animal = $this->findOneBy(array('ulnCountryCode' => $countryCode, 'ulnNumber' => $ulnNumber));

    return $animal;
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
      $animals = $this->getEntityManager()->getRepository(Constant::ANIMAL_REPOSITORY)->findBy($filterArray);
    } else if ($animalType == null && array_key_exists(Constant::IS_ALIVE_NAMESPACE, $filterArray)) {
      //filter animals by given isAlive state:{true, false}, belonging to user
      $animals = $this->getEntityManager()->getRepository(Constant::ANIMAL_REPOSITORY)->findBy($filterArray);
    } else if ($animalType != null) {
      //filter animals by given animal-type:{ram, ewe, neuter}, belonging to user
      switch ($animalType) {
        case AnimalObjectType::EWE:
          $animals = $this->getEntityManager()->getRepository(Constant::EWE_REPOSITORY)->findBy($filterArray);
          break;
        case AnimalObjectType::RAM:
          $animals = $this->getEntityManager()->getRepository(Constant::RAM_REPOSITORY)->findBy($filterArray);
          break;
        case AnimalObjectType::NEUTER:
          $animals = $this->getEntityManager()->getRepository(Constant::NEUTER_REPOSITORY)->findBy($filterArray);
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
    $locationRepository = $this->getEntityManager()
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
   * @param bool $showTransferring
   * @return array
   */
  public function getLiveStockAllLocations(Client $client, $isAlive = true, $isDeparted = false, $isExported = false, $showTransferring = false)
  {
    $animals = array();

    if ($showTransferring) {
      $transferState = AnimalTransferStatus::TRANSFERRING;
    } else {
      $transferState = AnimalTransferStatus::NULL;
    }

    foreach ($client->getCompanies() as $company) {
      foreach ($company->getLocations() as $location) {
        foreach ($location->getAnimals() as $animal) {

          $showAnimal = $animal->getIsAlive() == $isAlive
              && $animal->getIsExportAnimal() == $isExported
              && $animal->getIsDepartedAnimal() == $isDeparted
              && ($animal->getTransferState() == AnimalTransferStatus::NULL
                  || $animal->getTransferState() == $transferState);

          if ($showAnimal) {
            $animals[] = $animal;
          }

        }
      }
    }

    return $animals;
  }

  /**
   * @param Location $location
   * @param bool $isAlive
   * @param bool $isDeparted
   * @param bool $isExported
   * @param bool $showTransferring
   * @return array
   */
  public function getLiveStock(Location $location, $isAlive = true, $isDeparted = false, $isExported = false, $showTransferring = false)
  {
    $animals = array();

    if ($showTransferring) {
      $transferState = AnimalTransferStatus::TRANSFERRING;
    } else {
      $transferState = AnimalTransferStatus::NULL;
    }

    foreach ($location->getAnimals() as $animal) {

      $showAnimal = $animal->getIsAlive() == $isAlive
          && $animal->getIsExportAnimal() == $isExported
          && $animal->getIsDepartedAnimal() == $isDeparted
          && ($animal->getTransferState() == AnimalTransferStatus::NULL
              || $animal->getTransferState() == $transferState);

      if ($showAnimal) {
        $animals[] = $animal;
      }
    }

    return $animals;
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

      return $this->getEntityManager()->getRepository(Animal::class)
                  ->matching($criteria);
    }


  /**
   * @param $startId
   * @param $endId
   * @return Collection
   */
  public function getAnimalsByIdWithoutMixBlupBreedCode($startId, $endId)
  {
    $criteria = Criteria::create()
        ->where(Criteria::expr()->gte('id', $startId))
        ->andWhere(Criteria::expr()->lte('id', $endId))
        ->andWhere(Criteria::expr()->eq('mixBlupBreedCode', null))
        ->orderBy(['id' => Criteria::ASC])
    ;

    return $this->getEntityManager()->getRepository(Animal::class)
        ->matching($criteria);
  }
}
