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
  public function getBirths(Location $location, $state = null) {
    $retrievedBirths = $location->getBirths();

    return $this->getRequests($retrievedBirths, $state);
  }

  /**
   * @param Location $location
   * @param string $requestId
   * @return DeclareBirth|null
   */
  public function getBirthByRequestId(Location $location, $requestId) {
    $births = $this->getBirths($location);

    return $this->getRequestByRequestId($births, $requestId);
  }

  /**
   * Get a list of suggested candidate fathers based on matings done in within 145 + (-12 & +12) days, from now
   * and all other Rams.
   *
   * @param Ewe $mother
   * @return array
   */
  public function getCandidateFathers(Ewe $mother, \DateTime $dateOfbirth) {
    $em = $this->getEntityManager();
    $queryBuilder = $em->createQueryBuilder();

    $queryBuilder
      ->select('mate')
      ->from('AppBundle:Mate', 'mate')
      ->where($queryBuilder->expr()->andX(
        $queryBuilder->expr()->andX(
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

      if (array_key_exists($mating->getStudRam()->getId(), $fatherIds)) {
        continue;
      }

      $fatherIds[$mating->getStudRam()->getId()] = $mating->getStudRam()
        ->getId();

      /**
       * Computing candidate fathers based on registered matings for a given Ewe:
       *
       * Registered mating:
       * startDate: 01-10-2016 (inclusive)
       * eindDate: 15-11-2016 (inclusive)(for the dutchies: TOT EN MET)
       *
       *
       * matingDaysIntervalInDay = (matingEnd - matingStart)
       * = (15-11-2016 - 01-10-2016)
       * = 46 days
       *
       * pregnancyDays (system default) = 145
       *
       * lowerboundPregnancyDays = pregnancyDays - 12
       * = 145 - 12
       * = 133 days
       *
       * upperboundPregnancyDays = pregnancyDays + 12
       * = 145 + 12
       * = 157 days
       *
       * computedLitterDate = (matingStart + pregnancyDays)
       *
       * beginDatePotentialFather = ((matingStart + lowerboundPregnancyDays) = computedLitterDate)
       * = (1-10-2016 + 133 days)
       * = 11-02-2017
       *
       * enddatePotentialFatherhood = ((matingStart + lowerboundPregnancyDays) = litterDate) + matingDays
       * = (15-11-2016 + 157 days)
       * = 22-04-2017
       *
       * The computed begindate and enddate of a father for a given Ewe is, thus:
       *
       * beginDate: 11-02-2017 (inclusive)
       * endDate:   22-04-2017 (inclusive) (for the dutchies: TOT EN MET)
       *
       * if an actual birthdate is not in between (inclusive of boundaries) the beginDate and endDate, the candidate
       * father should not be a suggested father.
       *
       */
      $matingDaysOffset = 12;
      $pregnancyDays = 145;

      //Get matingPeriod in days
      $matingDays = TimeUtil::getAgeInDays($mating->getStartDate(), $mating->getEndDate());

      $lowerboundPregnancyDays = $pregnancyDays - $matingDaysOffset;
      $upperboundPregnancyDays = $pregnancyDays + $matingDaysOffset;

      //Compare if final father suggestion date is before dateOfBirth lower- & upperbound
      $expectedBirthDateLowerbound = clone $mating->getStartDate();
      $expectedBirthDateLowerbound->modify("+" . (string) $lowerboundPregnancyDays . " days");

      $expectedBirthDateUpperbound = clone $mating->getEndDate();
      $expectedBirthDateUpperbound->modify("+" . (string) $upperboundPregnancyDays . " days");

      //Get the date difference between the computed dateOfBirth and the actual given dateOfBirth
      //Check if it is betweeen date interval of given upperBound and lowerBound
      if (TimeUtil::isDateBetweenDates($dateOfbirth, $expectedBirthDateLowerbound, $expectedBirthDateUpperbound)) {
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
      ->from('AppBundle:Animal', 'animal')
      ->where($livestockEwesQueryBuilder->expr()->andX(
        $livestockEwesQueryBuilder->expr()->andX(
          $livestockEwesQueryBuilder->expr()->eq('animal.isAlive', 'true'),
          $livestockEwesQueryBuilder->expr()->eq('animal.gender', "'FEMALE'"),
          $livestockEwesQueryBuilder->expr()
            ->neq('animal.id', $mother->getId()),
          $livestockEwesQueryBuilder->expr()->orX(
            $livestockEwesQueryBuilder->expr()->isNull('animal.transferState'),
            $livestockEwesQueryBuilder->expr()
              ->neq('animal.transferState', "'TRANSFERRING'")
          )),
        $livestockEwesQueryBuilder->expr()
          ->eq('animal.location', $location->getId())
      ));

    $query = $livestockEwesQueryBuilder->getQuery();

    return $query->getResult();
  }


  /**
   * Return a list of children belonging to a given mother
   * @param Animal $mother
   * @return array
   */
  public function getChildrenOfEwe(Animal $mother) {

    $em = $this->getEntityManager();
    $livestockEwesQueryBuilder = $em->createQueryBuilder();

    $livestockEwesQueryBuilder
      ->select('animal')
      ->from('AppBundle:Animal', 'animal')
      ->where(
        $livestockEwesQueryBuilder->expr()->eq('animal.parentMother', $mother->getId())
      );

    $query = $livestockEwesQueryBuilder->getQuery();

    return $query->getResult();
  }
}