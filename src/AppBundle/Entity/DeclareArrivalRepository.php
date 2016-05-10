<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;

/**
 * Class DeclareArrivalRepository
 * @package AppBundle\Entity
 */
class DeclareArrivalRepository extends BaseRepository
{

  /**
   * @param $declareArrivalUpdate
   * @param $Id
   * @return null|object
   */
  public function updateDeclareArrivalMessage($declareArrivalUpdate, $Id) {

    $declareArrival = $this->findOneBy(array (Constant::REQUEST_ID_NAMESPACE => $Id));

    if($declareArrival == null) {
      return null;
    }

    if ($declareArrivalUpdate->getAnimal() != null) {
      $declareArrival->setAnimal($declareArrivalUpdate->getAnimal());
    }

    if ($declareArrivalUpdate->getArrivalDate() != null) {
      $declareArrival->setArrivalDate($declareArrivalUpdate->getArrivalDate());
    }

    if ($declareArrivalUpdate->getLocation() != null) {
      $declareArrival->setLocation($declareArrivalUpdate->getLocation());
    }

    if ($declareArrivalUpdate->getIsImportAnimal() != null) {
      $declareArrival->setIsImportAnimal($declareArrivalUpdate->getIsImportAnimal());
    }

    if($declareArrivalUpdate->getUbnPreviousOwner() != null) {
      $declareArrival->setUbnPreviousOwner($declareArrivalUpdate->getUbnPreviousOwner());
    }

    return $declareArrival;
  }

}