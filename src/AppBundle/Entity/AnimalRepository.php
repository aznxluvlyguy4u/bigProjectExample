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

    return $this->querier($countryCode, $ulnOrPedigreeCode);
  }

  /**
   * @param $countryCode
   * @param $ulnOrPedigreeCode
   * @return array|null
   */
  function findByCountryCodeAndUlnOrPedigree($countryCode, $ulnOrPedigreeCode)
  {
    return $this->querier($countryCode, $ulnOrPedigreeCode);
  }

  /**
   * @param $countryCode
   * @param $ulnOrPedigreeCode
   * @return array|null
   */
  private function querier($countryCode, $ulnOrPedigreeCode){

    $animal = null;
    $tagRepository = $this->getEntityManager()->getRepository(Constant::TAG_REPOSITORY);

    //Find animal through Tag ulnNumber
    $tag = $tagRepository->findByUlnNumberAndCountryCode($countryCode,$ulnOrPedigreeCode);


    if($tag != null) {
        $animal = $tag->getAnimal();
    } else { //Find animal through Animal pedigreeNumber
      $query = $this->getEntityManager()->getRepository(Constant::ANIMAL_REPOSITORY)->createQueryBuilder('animal')
        ->where('animal.pedigreeNumber = :pedigreeNumber')
        ->andWhere('animal.pedigreeCountryCode = :pedigreeCountryCode')
        ->setParameter('pedigreeNumber', $ulnOrPedigreeCode)
        ->setParameter('pedigreeCountryCode', $countryCode)
        ->getQuery();
      $animals = $query->getResult();

      if(sizeof($animals) > 0){
        $animal = $animals[0];
      }
    }

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
}
