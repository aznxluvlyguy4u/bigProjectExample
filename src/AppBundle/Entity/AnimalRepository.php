<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\AnimalType;

/**
 * Class AnimalRepository
 * @package AppBundle\Entity
 */
class AnimalRepository extends BaseRepository
{
  /**
   * @param $Id
   * @return array|null
   */
  function findByUlnOrPedigree($Id)
  {
    //Strip countryCode
    $countryCode = mb_substr($Id, 0, 2, 'utf-8');

    //Strip ulnCode or pedigreeCode
    $ulnOrPedigreeCode = mb_substr($Id, 2, strlen($Id));

    return $this->findByUlnOrPedigreeCountryCodeAndNumber($countryCode, $ulnOrPedigreeCode);
  }

  /**
   * @param $countryCode
   * @param $ulnOrPedigreeCode
   * @return Animal|null
   */
  function findByUlnOrPedigreeCountryCodeAndNumber($countryCode, $ulnOrPedigreeCode)
  {
    $animal = $this->findByUlnCountryCodeAndNumber($countryCode, $ulnOrPedigreeCode);

    if($animal != null) {
      return $animal;
    } else { //Find animal through Animal pedigreeNumber
      $animal = $this->findByPedigreeCountryCodeAndNumber($countryCode, $ulnOrPedigreeCode);
    }

    return $animal;
  }

  /**
   * @param $countryCode
   * @param $pedigreeNumber
   * @return null|Animal
   */
  public function findByPedigreeCountryCodeAndNumber($countryCode, $pedigreeNumber)
  {
    $animal = $this->findOneBy(array('pedigreeCountryCode'=>$countryCode, 'pedigreeNumber'=>$pedigreeNumber));

    return $animal;
  }

  /**
   * @param $countryCode
   * @param $ulnNumber
   * @return null|Animal
   */
  public function findByUlnCountryCodeAndNumber($countryCode, $ulnNumber)
  {
    $animal = $this->findOneBy(array('ulnCountryCode'=>$countryCode, 'ulnNumber'=>$ulnNumber));

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
        case AnimalType::EWE:
          $animals = $this->getEntityManager()->getRepository(Constant::EWE_REPOSITORY)->findBy($filterArray);
          break;
        case AnimalType::RAM:
          $animals = $this->getEntityManager()->getRepository(Constant::RAM_REPOSITORY)->findBy($filterArray);
          break;
        case AnimalType::NEUTER:
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
    if($animal == null) {
      return null;
    }

    $countryCode = $animal->getUlnCountryCode();
    $number = $animal->getUlnNumber();
    $retrievedAnimal = $this->findByUlnCountryCodeAndNumber($countryCode, $number);

    if($retrievedAnimal != null) {
        return $retrievedAnimal;
    } else {
      $countryCode = $animal->getPedigreeCountryCode();
      $number = $animal->getPedigreeNumber();
      $retrievedAnimal = $this->findByPedigreeCountryCodeAndNumber($countryCode, $number);
    }

    return $retrievedAnimal;
  }


}
