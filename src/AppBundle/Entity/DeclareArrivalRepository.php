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

    if($state == null) {
      $declareArrivals = $retrievedArrivals;

    } else {
      $declareArrivals = new ArrayCollection();
      foreach($retrievedArrivals as $retrievedArrival) {
        if($retrievedArrival->getRequestState() == $state) {
          $declareArrivals->add($retrievedArrival);
        }
      }
    }

    return $declareArrivals;
  }

  /**
   * @param Client $client
   * @param string $requestId
   * @return DeclareArrival|null
   */
  public function getArrivalsById(Client $client, $requestId)
  {
    $arrivals = $this->getArrivals($client);

    foreach($arrivals as $arrival) {
      $foundRequestId = $arrival->getRequestId($requestId);
      if($foundRequestId == $requestId) {
        return $arrival;
      }
    }

    return null;
  }

}