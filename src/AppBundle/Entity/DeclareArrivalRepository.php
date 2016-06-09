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
   * @param Client $client
   * @param string $state
   * @return ArrayCollection
   */
  public function getArrivals(Client $client, $state = null)
  {
    $location = $client->getCompanies()->get(0)->getLocations()->get(0);
    $retrievedArrivals = $location->getArrivals();

    return $this->getRequests($retrievedArrivals, $state);
  }

  /**
   * @param Client $client
   * @param string $requestId
   * @return DeclareArrival|null
   */
  public function getArrivalByRequestId(Client $client, $requestId)
  {
    $arrivals = $this->getArrivals($client);

    return $this->getRequestByRequestId($arrivals, $requestId);
  }

}