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
   * @param $declareExportUpdate
   * @param $Id
   * @return null|object
   */
  public function updateDeclareExportMessage($declareExportUpdate, $Id) {
    $declareExport = $this->findOneBy(array (Constant::REQUEST_ID_NAMESPACE => $Id));

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

    if($state == null) {
      $declareExports = $retrievedExports;

    } else {
      $declareExports = new ArrayCollection();
      foreach($retrievedExports as $retrievedImport) {
        if($retrievedImport->getRequestState() == $state) {
          $declareExports->add($retrievedImport);
        }
      }
    }

    return $declareExports;
  }

  /**
   * @param Client $client
   * @param string $requestId
   * @return DeclareExport|null
   */
  public function getExportsById(Client $client, $requestId)
  {
    $exports = $this->getExports($client);

    foreach($exports as $export) {
      $foundRequestId = $export->getRequestId($requestId);
      if($foundRequestId == $requestId) {
        return $export;
      }
    }

    return null;
  }
}