<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;
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
}