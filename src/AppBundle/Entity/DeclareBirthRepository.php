<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareBirthRepository
 * @package AppBundle\Entity
 */
class DeclareBirthRepository extends BaseRepository {

  /**
   * @param Location $location
   * @param string $state
   * @return ArrayCollection
   */
  public function getBirths(Location $location, $state = null)
  {
    $retrievedBirths = $location->getBirths();

    return $this->getRequests($retrievedBirths, $state);
  }

  /**
   * @param Location $location
   * @param string $requestId
   * @return DeclareBirth|null
   */
  public function getBirthByRequestId(Location $location, $requestId)
  {
    $births = $this->getBirths($location);

    return $this->getRequestByRequestId($births, $requestId);
  }

  /**
   * Get a list of suggested candidate fathers based on matings done in within 170 days from now
   * and all other Rams on current location.
   * 
   * @param Location $location
   * @param Ewe $mother
   * @return array
   */
  public function getCandidateFathers(Location $location, Ewe $mother) {
    $em = $this->getEntityManager();
    $queryBuilder = $em->createQueryBuilder();

    $queryBuilder
      ->select('mate')
      ->from ('AppBundle:Mate', 'mate')
      ->where($queryBuilder->expr()->andX(
        $queryBuilder->expr()->andX(
          $queryBuilder->expr()->eq('mate.location', $location->getId()),
          $queryBuilder->expr()->eq('mate.isOverwrittenVersion', 'false'),
          $queryBuilder->expr()->eq('mate.studEwe', $mother->getId()),
          $queryBuilder->expr()->orX(
            $queryBuilder->expr()->isNull('mate.isApprovedByThirdParty'),
            $queryBuilder->expr()->eq('mate.isApprovedByThirdParty', 'true')
          )
        )
      ))
    ;

    $query = $queryBuilder->getQuery();
    $result = $query->getResult();
    $candidateFathers = [];

    $now = new \DateTime();

    /** @var Mate $mating */
    foreach ($result as $mating) {

      //Check if mating is within the accepted interval of 170 days from now
      $dateInterval = $mating->getEndDate()->diff($now);

      if(TimeUtil::getAgeInDays($now, $mating->getEndDate()) <= 170){
        $candidateFathers[] = $mating->getStudRam();
      }
    }

    return $candidateFathers;
  }

  /**
   * Get a list of suggested candidate surrogates based on births done in within 6 months from now
   * and all other Ewes on current location.
   * 
   * @param Location $location
   * @param Ewe $mother
   * @return array
   */
  public function getCandidateSurrogateMothers(Location $location, Ewe $mother) {
    $em = $this->getEntityManager();
    $livestockEwesQueryBuilder = $em->createQueryBuilder();

    $livestockEwesQueryBuilder
      ->select('animal')
      ->from ('AppBundle:Animal', 'animal')
      ->where($livestockEwesQueryBuilder->expr()->andX(
        $livestockEwesQueryBuilder->expr()->andX(
          $livestockEwesQueryBuilder->expr()->eq('animal.isAlive', 'true'),
          $livestockEwesQueryBuilder->expr()->eq('animal.gender', "'FEMALE'"),
          $livestockEwesQueryBuilder->expr()->neq('animal.id', $mother->getId()),
          $livestockEwesQueryBuilder->expr()->orX(
            $livestockEwesQueryBuilder->expr()->isNull('animal.transferState'),
            $livestockEwesQueryBuilder->expr()->neq('animal.transferState', "'TRANSFERRING'")
          )),
        $livestockEwesQueryBuilder->expr()->eq('animal.location', $location->getId())
      ));
    
    $query = $livestockEwesQueryBuilder->getQuery();
    
    return $query->getResult();
  }
}