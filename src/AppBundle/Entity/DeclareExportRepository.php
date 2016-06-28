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
   * @param Location $location
   * @param $id
   * @return null|DeclareExport
   */
  public function updateDeclareExportMessage($declareExportUpdate, Location $location, $id) {

    $declareExport = $this->getExportByRequestId($location, $id);

    if($declareExport == null) {
      return null;
    }

    if ($declareExportUpdate->getAnimal() != null) {
      $declareExport->setAnimal($declareExportUpdate->getAnimal());
    }

    if ($declareExportUpdate->getExportDate() != null) {
      $declareExport->setExportDate($declareExportUpdate->getExportDate());
    }

    if ($declareExportUpdate->getReasonOfExport() != null) {
      $declareExport->setReasonOfExport($declareExportUpdate->getReasonOfExport());
    }
    
    if ($declareExportUpdate->getLocation() != null) {
      $declareExport->setLocation($declareExportUpdate->getLocation());
    }

    return $declareExport;
  }

  /**
   * @param Location $location
   * @param string $state
   * @return ArrayCollection
   */
  public function getExports(Location $location, $state = null)
  {
    $retrievedExports = $location->getExports();

    return $this->getRequests($retrievedExports, $state);
  }

  /**
   * @param Location $location
   * @param string $requestId
   * @return DeclareExport|null
   */
  public function getExportByRequestId(Location $location, $requestId)
  {
    $exports = $this->getExports($location);

    return $this->getRequestByRequestId($exports, $requestId);
  }
}