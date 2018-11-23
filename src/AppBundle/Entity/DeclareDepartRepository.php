<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\RequestUtil;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareDepartRepository
 * @package AppBundle\Entity
 */
class DeclareDepartRepository extends BaseRepository {

  /**
   * @param DeclareDepart $declareDepartUpdate
   * @param Location $location
   * @param $id
   * @return null|DeclareDepart
   */
  public function updateDeclareDepartMessage($declareDepartUpdate, Location $location, $id) {

    $declareDepart = $this->getDepartureByRequestId($location, $id);

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

    if ($declareDepartUpdate->getReasonOfDepart() != null) {
      $declareDepart->setReasonOfDepart($declareDepartUpdate->getReasonOfDepart());
    }

    if($declareDepartUpdate->getUbnNewOwner() != null) {
      $declareDepart->setUbnNewOwner($declareDepartUpdate->getUbnNewOwner());
    }

    return $declareDepart;
  }

  /**
   * @param Location $location
   * @param string $state
   * @return ArrayCollection
   */
  public function getDepartures(Location $location, $state = null)
  {
    $retrievedDeparts = $location->getDepartures();

    return $this->getRequests($retrievedDeparts, $state);
  }

  /**
   * @param Location $location
   * @param string $requestId
   * @return DeclareDepart|null
   */
  public function getDepartureByRequestId(Location $location, $requestId)
  {
    $departs = $this->getDepartures($location);

    return $this->getRequestByRequestId($departs, $requestId);
  }


    /**
     * @param ArrayCollection $content
     * @param Location $location
     * @param bool $includeSecondaryInformation
     * @return ArrayCollection
     */
    public function findByDeclareInput(ArrayCollection $content, Location $location,
                                       bool $includeSecondaryInformation = false)
    {
        $animalArray = $content->get(Constant::ANIMAL_NAMESPACE);
        $reasonOfDepart = $content->get(JsonInputConstant::REASON_OF_DEPART);
        $ubnNewOwner = $content->get(JsonInputConstant::UBN_NEW_OWNER);

        $departDate = RequestUtil::getDateTimeFromContent($content, JsonInputConstant::DEPART_DATE);

        $ulnCountryCode = ArrayUtil::get(JsonInputConstant::ULN_COUNTRY_CODE, $animalArray);
        $ulnNumber = ArrayUtil::get(JsonInputConstant::ULN_NUMBER, $animalArray);

        $searchValues = [
            'departDate' => $departDate,
            'ulnCountryCode' => $ulnCountryCode,
            'ulnNumber' => $ulnNumber,
            'ubnNewOwner' => $ubnNewOwner,
            'ubn' => $location->getUbn(),
        ];

        if ($includeSecondaryInformation) {
            $searchValues['reasonOfDepart'] = $reasonOfDepart;
        }

        $departs = $this->findBy($searchValues);

        if (is_array($departs)) {
            return new ArrayCollection($departs);
        }

        return new ArrayCollection();
    }

}