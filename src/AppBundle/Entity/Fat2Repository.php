<?php

namespace AppBundle\Entity;
use Doctrine\Common\Collections\Criteria;

/**
 * Class Fat2Repository
 * @package AppBundle\Entity
 */
class Fat2Repository extends BaseRepository {

  /**
   * @param Animal $animal
   * @return float
   */
  public function getLatestFat2(Animal $animal)
  {
    //Measurement Criteria
    $criteria = Criteria::create()
      ->where(Criteria::expr()->eq('animal', $animal))
      ->orderBy(['measurementDate' => Criteria::DESC])
      ->setMaxResults(1);

    //Fat2
    $latestFat2 = $this->getEntityManager()->getRepository(Fat2::class)
      ->matching($criteria);

    if(sizeof($latestFat2) > 0) {
      $latestFat2 = $latestFat2->get(0);
      $latestFat2 = $latestFat2->getFat();
    } else {
      $latestFat2 = 0.00;
    }
    return $latestFat2;
  }

}