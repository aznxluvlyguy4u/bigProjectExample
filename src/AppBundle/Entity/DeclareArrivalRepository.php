<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\RequestUtil;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareArrivalRepository
 * @package AppBundle\Entity
 */
class DeclareArrivalRepository extends BaseRepository
{

  /**
   * @param Location $location
   * @param string $state
   * @return ArrayCollection
   */
  public function getArrivals(Location $location, $state = null)
  {
    $retrievedArrivals = $location->getArrivals();

    return $this->getRequests($retrievedArrivals, $state);
  }

  /**
   * @param Location $location
   * @param string $requestId
   * @return DeclareArrival|null
   */
  public function getArrivalByRequestId(Location $location, $requestId)
  {
    $arrivals = $this->getArrivals($location);

    return $this->getRequestByRequestId($arrivals, $requestId);
  }


    /**
     * @param ArrayCollection $content
     * @param Location $location
     * @param bool $pedigreeCodeExists
     * @return ArrayCollection
     */
  public function findByDeclareInput(ArrayCollection $content, Location $location,
                                     $pedigreeCodeExists = false)
  {
      $animalArray = $content->get(Constant::ANIMAL_NAMESPACE);
      $ubnPreviousOwner = $content->get(JsonInputConstant::UBN_PREVIOUS_OWNER);
      $ubnDestination = $location->getUbn();

      $arrivalDate = RequestUtil::getDateTimeFromContent($content, JsonInputConstant::ARRIVAL_DATE);

      if ($pedigreeCodeExists) {
          $pedigreeCountryCode = ArrayUtil::get(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $animalArray);
          $pedigreeNumber = ArrayUtil::get(JsonInputConstant::PEDIGREE_NUMBER, $animalArray);

          $arrivals = $this->findBy([
              'arrivalDate' => $arrivalDate,
              'pedigreeCountryCode' => $pedigreeCountryCode,
              'pedigreeNumber' => $pedigreeNumber,
              'ubnPreviousOwner' => $ubnPreviousOwner,
              'ubn' => $ubnDestination,
          ]);

      } else {
          $ulnCountryCode = ArrayUtil::get(JsonInputConstant::ULN_COUNTRY_CODE, $animalArray);
          $ulnNumber = ArrayUtil::get(JsonInputConstant::ULN_NUMBER, $animalArray);

          $arrivals = $this->findBy([
              'arrivalDate' => $arrivalDate,
              'ulnCountryCode' => $ulnCountryCode,
              'ulnNumber' => $ulnNumber,
              'ubnPreviousOwner' => $ubnPreviousOwner,
              'ubn' => $ubnDestination,
          ]);
      }

      if (is_array($arrivals)) {
          return new ArrayCollection($arrivals);
      }

      return new ArrayCollection();
  }
}