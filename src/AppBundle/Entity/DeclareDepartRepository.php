<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareDepartRepository
 * @package AppBundle\Entity
 */
class DeclareDepartRepository extends BaseRepository {

  /**
   * @param $declareDepartUpdate
   * @param $Id
   * @return null|object
   */
  public function updateDeclareDepartMessage($declareDepartUpdate, $Id) {
    $declareDepart = $this->findOneBy(array (Constant::REQUEST_ID_NAMESPACE => $Id));

    if($declareDepart == null) {
      return null;
    }

    if ($declareDepartUpdate->getAnimal() != null) {
      $declareDepart->setAnimal($declareDepartUpdate->getAnimal());
    }

    if ($declareDepartUpdate->getDepartDate() != null) {
      $declareDepart->setDepartDate($declareDepartUpdate->getDepartDate());
    }

    if ($declareDepartUpdate->getLocation() != null) {
      $declareDepart->setLocation($declareDepartUpdate->getLocation());
    }

    if($declareDepartUpdate->getUbnNewOwner() != null) {
      $declareDepart->setUbnNewOwner($declareDepartUpdate->getUbnNewOwner());
    }

    return $declareDepart;
  }

  /**
   * @param Client $client
   * @param string $state
   * @return ArrayCollection
   */
  public function getDepartures(Client $client, $state = null)
  {
    $location = $client->getCompanies()->get(0)->getLocations()->get(0);
    $retrievedDeparts = $location->getDepartures();

    return $this->getRequests($retrievedDeparts, $state);
  }

  /**
   * @param Client $client
   * @param string $requestId
   * @return DeclareDepart|null
   */
  public function getDeparturesById(Client $client, $requestId)
  {
    $departs = $this->getDepartures($client);

    return $this->getRequestsById($departs, $requestId);
  }
}