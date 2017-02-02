<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class DeclareBirthRepository
 * @package AppBundle\Entity
 */
class DeclareBirthRepository extends BaseRepository {

  // the accepted interval of 145 with an offset of PLUS and MINUS 12 days
  const MATING_CANDIDATE_START_OFFSET = 133;
  const MATING_CANDIDATE_END_OFFSET = 157;

  /**
   * @param Location $location
   * @param string $state
   * @return ArrayCollection
   */
  public function getBirths(Location $location, $state = null)
  {
    $retrievedBirths = $location->getBirths();

    return $this->getRequests($retrievedBirths, $state);
  }

  /**
   * @param Location $location
   * @param string $requestId
   * @return DeclareBirth|null
   */
  public function getBirthByRequestId(Location $location, $requestId)
  {
    $births = $this->getBirths($location);

    return $this->getRequestByRequestId($births, $requestId);
  }

  /**
   * Get a list of suggested candidate fathers based on matings done in within 145 + (-12 & +12) days, from now
   * and all other Rams on current location.
   * 
   * @param Location $location
   * @param Ewe $mother
   * @return array
   */
  public function getCandidateFathers(Location $location, Ewe $mother, \DateTime $dateOfbirth) {
    $em = $this->getEntityManager();
    $queryBuilder = $em->createQueryBuilder();

    $queryBuilder
      ->select('mate')
      ->from ('AppBundle:Mate', 'mate')
      ->where($queryBuilder->expr()->andX(
        $queryBuilder->expr()->andX(
          $queryBuilder->expr()->eq('mate.location', $location->getId()),
          $queryBuilder->expr()->eq('mate.isOverwrittenVersion', 'false'),
          $queryBuilder->expr()->eq('mate.studEwe', $mother->getId()),
          $queryBuilder->expr()->eq('mate.requestState', "'FINISHED'"),
          $queryBuilder->expr()->orX(
            $queryBuilder->expr()->isNull('mate.isApprovedByThirdParty'),
            $queryBuilder->expr()->eq('mate.isApprovedByThirdParty', 'true')
          )
        )
      ));

    $query = $queryBuilder->getQuery();
    $result = $query->getResult();
    $candidateFathers = [];

    $fatherIds = [];

    /** @var Mate $mating */
    foreach ($result as $mating) {

      if(array_key_exists($mating->getStudRam()->getId(), $fatherIds)) {
        continue;
      }

      $fatherIds[$mating->getStudRam()->getId()] = $mating->getStudRam()->getId();

      /**
       * Registered mating:
       *
       * start: 01-10-2016
       * eind: 15-11-2016
       *
       * reserveDays = 24 (2x 12 days => lowerbound - 12 | upperbound + 12)
       *
       * matingDays = (matingEnd - matingStart) + reserveDays
       * = (15-11-2016 - 01-10-2016) + reserveDays
       * = 46 + 24
       * = 70 days
       *
       * pregnancyDays = 145
       *
       * lowerboundPregnancyDays = pregnancyDays - 12
       * = 145 - 12
       * = 133 days
       *
       * upperboundPregnancyDays = pregnancyDays + 12
       * = 145 + 12
       * = 157 days // not used
       *
       * enddatePotentialFatherhood = ((matingStart + lowerboundPregnancyDays) = litterDate) + matingDays
       * = (1-10-2016 + 133 days) + 70 days
       * = 11-02-2017 + 70 days
       * = 22-04-2017
       *
       * Thus the enddate of a potential father:
       *  22-04-2017 (inclusive) (for the dutchies: tot en MET)
       */
      $matingDaysOffset = 12;
      $pregnancyDays = 145;

      //Get matingPeriod in days
      $matingDays = TimeUtil::getAgeInDays($mating->getStartDate(), $mating->getEndDate());
      $matingDays += $matingDaysOffset * 2;

      $lowerboundPregnancyDays = $pregnancyDays - $matingDaysOffset;

      $enddatePotentialFatherhood = clone $mating->getStartDate();
      $enddatePotentialFatherhood->modify("+" .(string)$lowerboundPregnancyDays ." days");
      $enddatePotentialFatherhood->modify("+" .(string)$matingDays ." days");

      //Compare if final father suggestion date is before dateOfBirth lower- & upperbound
      $expectedBirthDateLowerbound = clone $mating->getStartDate();
      $expectedBirthDateLowerbound->modify("1" .(string)$lowerboundPregnancyDays ." days");

      $expectedBirthDateUpperbound = clone $mating->getStartDate();
      $expectedBirthDateUpperbound->modify("+" .(string)$lowerboundPregnancyDays ." days");

      if(TimeUtil::isDateBetweenDates($dateOfbirth, $expectedBirthDateLowerbound,$expectedBirthDateUpperbound)) {
        $candidateFathers[] = $mating->getStudRam();
      }
    }

    $fatherIds = null;

    return $candidateFathers;
  }

  /**
   * Get a list of suggested candidate surrogates based on births done in within 6 months from now
   * and all other Ewes on current location.
   * 
   * @param Location $location
   * @param Ewe $mother
   * @return array
   */
  public function getCandidateSurrogateMothers(Location $location, Ewe $mother) {
    $em = $this->getEntityManager();
    $livestockEwesQueryBuilder = $em->createQueryBuilder();

    $livestockEwesQueryBuilder
      ->select('animal')
      ->from ('AppBundle:Animal', 'animal')
      ->where($livestockEwesQueryBuilder->expr()->andX(
        $livestockEwesQueryBuilder->expr()->andX(
          $livestockEwesQueryBuilder->expr()->eq('animal.isAlive', 'true'),
          $livestockEwesQueryBuilder->expr()->eq('animal.gender', "'FEMALE'"),
          $livestockEwesQueryBuilder->expr()->neq('animal.id', $mother->getId()),
          $livestockEwesQueryBuilder->expr()->orX(
            $livestockEwesQueryBuilder->expr()->isNull('animal.transferState'),
            $livestockEwesQueryBuilder->expr()->neq('animal.transferState', "'TRANSFERRING'")
          )),
        $livestockEwesQueryBuilder->expr()->eq('animal.location', $location->getId())
      ));
    
    $query = $livestockEwesQueryBuilder->getQuery();
    
    return $query->getResult();
  }
}