<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareExportRepository
 * @package AppBundle\Entity
 */
class DeclareExportRepository extends BaseRepository {

  /**
   * @param DeclareExport $declareExportUpdate
   * @param Client $client
   * @param $id
   * @return null|DeclareExport
   */
  public function updateDeclareExportMessage($declareExportUpdate, $client, $id) {

    $declareExport = $this->getExportByRequestId($client, $id);

    if($declareExport == null) {
      return null;
    }

    if ($declareExportUpdate->getAnimal() != null) {
      $declareExport->setAnimal($declareExportUpdate->getAnimal());
    }

    if ($declareExportUpdate->getExportDate() != null) {
      $declareExport->setExportDate($declareExportUpdate->getExportDate());
    }

    if ($declareExportUpdate->getLocation() != null) {
      $declareExport->setLocation($declareExportUpdate->getLocation());
    }

    return $declareExport;
  }

  /**
   * @param Client $client
   * @param string $state
   * @return ArrayCollection
   */
  public function getExports(Client $client, $state = null)
  {
    $location = $client->getCompanies()->get(0)->getLocations()->get(0);
    $retrievedExports = $location->getExports();

    return $this->getRequests($retrievedExports, $state);
  }

  /**
   * @param Client $client
   * @param string $requestId
   * @return DeclareExport|null
   */
  public function getExportByRequestId(Client $client, $requestId)
  {
    $exports = $this->getExports($client);

    return $this->getRequestByRequestId($exports, $requestId);
  }
}