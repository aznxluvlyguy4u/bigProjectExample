<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;

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

}