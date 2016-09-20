<?php

namespace AppBundle\Entity;
use Doctrine\Common\Collections\Criteria;

/**
 * Class Fat1Repository
 * @package AppBundle\Entity
 */
class Fat1Repository extends BaseRepository {

  /**
   * @param Animal $animal
   * @return float
   */
  public function getLatestFat1(Animal $animal)
  {
    //Measurement Criteria
    $criteria = Criteria::create()
      ->where(Criteria::expr()->eq('animal', $animal))
      ->orderBy(['measurementDate' => Criteria::DESC])
      ->setMaxResults(1);

    //Fat1
    $latestFat1 = $this->getManager()->getRepository(Fat1::class)
      ->matching($criteria);

    if(sizeof($latestFat1) > 0) {
      $latestFat1 = $latestFat1->get(0);
      $latestFat1 = $latestFat1->getFat();
    } else {
      $latestFat1 = 0.00;
    }
    return $latestFat1;
  }

}