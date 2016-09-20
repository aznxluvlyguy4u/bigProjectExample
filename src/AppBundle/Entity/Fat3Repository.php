<?php

namespace AppBundle\Entity;
use Doctrine\Common\Collections\Criteria;

/**
 * Class Fat3Repository
 * @package AppBundle\Entity
 */
class Fat3Repository extends BaseRepository {

  /**
   * @param Animal $animal
   * @return float
   */
  public function getLatestFat3(Animal $animal)
  {
    //Measurement Criteria
    $criteria = Criteria::create()
      ->where(Criteria::expr()->eq('animal', $animal))
      ->orderBy(['measurementDate' => Criteria::DESC])
      ->setMaxResults(1);

    //Fat3
    $latestFat3 = $this->getManager()->getRepository(Fat3::class)
      ->matching($criteria);

    if(sizeof($latestFat3) > 0) {
      $latestFat3 = $latestFat3->get(0);
      $latestFat3 = $latestFat3->getFat();
    } else {
      $latestFat3 = 0.00;
    }
    return $latestFat3;
  }

}