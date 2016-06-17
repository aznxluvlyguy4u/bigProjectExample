<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestStateType;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareArrivalRepository
 * @package AppBundle\Entity
 */
class DeclareArrivalRepository extends BaseRepository
{

  /**
   * @param Location $location
   * @param string $state
   * @return ArrayCollection
   */
  public function getArrivals(Location $location, $state = null)
  {
    $retrievedArrivals = $location->getArrivals();

    return $this->getRequests($retrievedArrivals, $state);
  }

  /**
   * @param Location $location
   * @param string $requestId
   * @return DeclareArrival|null
   */
  public function getArrivalByRequestId(Location $location, $requestId)
  {
    $arrivals = $this->getArrivals($location);

    return $this->getRequestByRequestId($arrivals, $requestId);
  }

}