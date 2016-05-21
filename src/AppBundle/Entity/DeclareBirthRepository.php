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
   * @param Client $client
   * @param string $state
   * @return ArrayCollection
   */
  public function getBirths(Client $client, $state = null)
  {
    $location = $client->getCompanies()->get(0)->getLocations()->get(0);
    $retrievedBirths = $location->getBirths();

    return $this->getRequests($retrievedBirths, $state);
  }

  /**
   * @param Client $client
   * @param string $requestId
   * @return DeclareBirth|null
   */
  public function getBirthByRequestId(Client $client, $requestId)
  {
    $births = $this->getBirths($client);

    return $this->getRequestByRequestId($births, $requestId);
  }
}