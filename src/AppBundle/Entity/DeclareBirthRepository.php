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

    if($state == null) {
      $declareBirths = $retrievedBirths;

    } else {
      $declareBirths = new ArrayCollection();
      foreach($retrievedBirths as $retrievedBirth) {
        if($retrievedBirth->getRequestState() == $state) {
          $declareBirths->add($retrievedBirth);
        }
      }
    }

    return $declareBirths;
  }

  /**
   * @param Client $client
   * @param string $requestId
   * @return DeclareBirth|null
   */
  public function getBirthsById(Client $client, $requestId)
  {
    $births = $this->getBirths($client);

    foreach($births as $birth) {
      $foundRequestId = $birth->getRequestId($requestId);
      if($foundRequestId == $requestId) {
        return $birth;
      }
    }

    return null;
  }
}