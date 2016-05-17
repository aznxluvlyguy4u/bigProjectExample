<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;

/**
 * Class DeclareImportRepository
 * @package AppBundle\Entity
 */
class DeclareImportRepository extends BaseRepository {

  /**
   * @param $declareImportUpdate
   * @param $Id
   * @return JsonResponse|null|object
   */
  public function updateDeclareImportMessage($declareImportUpdate, $Id) {
    $declareImport = $this->findOneBy(array (Constant::REQUEST_ID_NAMESPACE => $Id));

    if($declareImport == null) {
      return new JsonResponse(array("message"=>"No DeclareImport found with request_id:" . $Id), 204);
    }

    if ($declareImportUpdate->getAnimal() != null) {
      $declareImport->setAnimal($declareImportUpdate->getAnimal());
    }

    if ($declareImportUpdate->getImportDate() != null) {
      $declareImport->setImportDate($declareImportUpdate->getImportDate());
    }

    if ($declareImportUpdate->getLocation() != null) {
      $declareImport->setLocation($declareImportUpdate->getLocation());
    }

    if ($declareImportUpdate->getIsImportAnimal() != null) {
      $declareImport->setIsImportAnimal($declareImportUpdate->getIsImportAnimal());
    }

    if ($declareImportUpdate->getAnimalCountryOrigin() != null) {
      $declareImport->setAnimalCountryOrigin($declareImportUpdate->getAnimalCountryOrigin());
    }

    return $declareImport;
  }
}