<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class DeclareImportRepository
 * @package AppBundle\Entity
 */
class DeclareImportRepository extends BaseRepository {

  /**
   * @param DeclareImport $declareImportUpdate
   * @param integer $Id
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

  /**
   * @param Location $location
   * @param string $state
   * @return ArrayCollection
   */
  public function getImports(Location $location, $state = null)
  {
    $retrievedImports = $location->getImports();

    return $this->getRequests($retrievedImports, $state);
  }

  /**
   * @param Location $location
   * @param string $requestId
   * @return DeclareImport|null
   */
  public function getImportByRequestId(Location $location, $requestId)
  {
    $imports = $this->getImports($location);

    return $this->getRequestByRequestId($imports, $requestId);
  }
}