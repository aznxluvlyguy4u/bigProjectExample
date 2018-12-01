<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;
use AppBundle\Enumerator\Country;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Class LocationRepository
 * @package AppBundle\Entity
 */
class LocationRepository extends BaseRepository
{

  /**
   * @param Client $client
   * @return ArrayCollection
   */
  public function findByUser(Client $client)
  {
    //Result set
    $locations = new ArrayCollection();

    foreach($client->getCompanies() as $company) {
      //Get locations of every company and add it to result set
      foreach($company->getLocations() as $location) {
        $locations->add($location);
      }
    }

    return $locations;
  }

  /**
   * @param Client $client
   * @param string $ubn
   * @return Location|null
   */
  public function findOfClientByUbn(Client $client, $ubn)
  {
    //Get companies of user
    $companies = $client->getCompanies();

    foreach($companies as $company) {
      foreach($company->getLocations() as $location) {
        if($location->getUbn() == $ubn) {
          return $location;
        }
      }
    }

    return null;
  }

  /**
   * @param $ubn
   * @return null|Location
   */
  public function findOneByActiveUbn($ubn)
  {
    return $this->findOneBy(['ubn' => $ubn, 'isActive' => true]);
  }


  /**
   * @param $ubn
   * @return null|Location
   */
  public function findOnePrioritizedByActiveUbn($ubn)
  {
    $location = $this->findOneByActiveUbn($ubn);
    if($location) {
      return $location;
    }
    return $this->findOneBy(['ubn' => $ubn]);
  }

  /**
   * @param array $location
   * @return null|object
   */
  public function findByLocationArray(array $location)
  {
    return $this->findOneByActiveUbn($location[Constant::UBN_NAMESPACE]);
  }

  /**
   * @param Client $client
   * @return ArrayCollection
   */
  public function findAllLocationsOfClient(Client $client)
  {
    $locations = new ArrayCollection();

    foreach ($client->getCompanies() as $company) {
      foreach ($company->getLocations() as $location) {
        $locations->add($location);
      }
    }

    return $locations;
  }


  /**
   * The location ids are returned by ubn.
   * Active locations are prioritized
   *
   * @return array
   */
  public function getLocationIdsByUbn()
  {
    $sql = "SELECT ubn, is_active, id FROM location
            ORDER BY ubn ASC , is_active DESC ";
    //The active locations are returned first
    $results = $this->getConnection()->query($sql)->fetchAll();

    $locationIdsByUbn = [];

    foreach ($results as $result) {
      $ubn = $result['ubn'];
      $id = $result['id'];
      
      if(!array_key_exists($ubn, $locationIdsByUbn)) {
        $locationIdsByUbn[$ubn] = intval($id);
      }
    }
    
    return $locationIdsByUbn;
  }


  /**
   * @param int $limit
   * @return array
   * @throws \Doctrine\DBAL\DBALException
   */
  public function findLocationsWithHighestAnimalCount($limit = 10)
  {
    $sql = "SELECT l.id, l.ubn, c.company_name, z.count, t.access_token FROM location l
                    INNER JOIN (
                                   SELECT a.location_id, COUNT(*) as count FROM animal a
                                   GROUP BY a.location_id
                                   --HAVING COUNT(*) > 400
                               )z ON z.location_id = l.id
                    INNER JOIN company c ON c.id = l.company_id
                    LEFT JOIN (
                        SELECT MAX(tt.code) as access_token, owner_id FROM token tt
                        WHERE tt.type = 'ACCESS'
                        GROUP BY owner_id
                    )t ON c.owner_id = t.owner_id
                WHERE l.is_active = TRUE AND c.is_active = TRUE
                ORDER BY count DESC LIMIT ".$limit;
    return $this->getConnection()->query($sql)->fetchAll();
  }


    /**
     * @param array $ubns
     * @return \Doctrine\ORM\Query|null
     */
  public function getLocationsQueryByUbns(array $ubns = [])
  {
      if (count($ubns) === 0) { return null; }

      $qb = $this->getManager()->createQueryBuilder();

      $qb
          ->select('location')
          ->from(Location::class, 'location')
      ;

      $count = 1;
      foreach ($ubns as $ubn) {

          $ubnQuery = $qb->expr()->andX(
              $qb->expr()->eq('location.ubn', ':ubn'.$count),
              $qb->expr()->eq('location.isActive', StringUtil::getBooleanAsString(true))
          );

          $qb->orWhere($ubnQuery);
          $qb->setParameter('ubn'.$count++, $ubn, Type::STRING);
      }

      return $qb->getQuery();
  }


    /**
     * @param int $hasNotBeenSyncedForAtLeastThisAmountOfDays
     * @param bool $onlyIncludeRvoLeading
     * @return array
     * @throws \Exception
     */
  public function getLocationsNonSyncedLocations($hasNotBeenSyncedForAtLeastThisAmountOfDays = 7,
                                                 bool $onlyIncludeRvoLeading = false)
  {
      if (!ctype_digit($hasNotBeenSyncedForAtLeastThisAmountOfDays) && !is_int($hasNotBeenSyncedForAtLeastThisAmountOfDays)) {
          throw new \Exception('hasNotBeenSyncedForAtLeastThisAmountOfDays should be an integer');
      }

      $minLogDate = new \DateTime('- '.$hasNotBeenSyncedForAtLeastThisAmountOfDays.'days');

      $addressQb = $this->getManager()->createQueryBuilder();
      $addressQb
          ->select('a')
          ->from(Address::class, 'a')
          ->innerJoin('a.countryDetails', 'c', Join::WITH, $addressQb->expr()->eq('a.countryDetails', 'c.id'))
          ->where($addressQb->expr()->eq('c.code',"'".Country::NL."'"))
      ;

      $retrieveAnimalsLocationQb = $this->getManager()->createQueryBuilder();
      $retrieveAnimalsLocationQb
          ->select('(l)')
          ->from(RetrieveAnimals::class, 'r')
          ->innerJoin('r.location', 'l', Join::WITH, $retrieveAnimalsLocationQb->expr()->eq('r.location', 'l.id'))
          ->where(':minLogDate <= r.logDate')
          ->andWhere($retrieveAnimalsLocationQb->expr()->eq('r.requestState', "'".RequestStateType::FINISHED."'"))
      ;
      if ($onlyIncludeRvoLeading) {
          $retrieveAnimalsLocationQb->andWhere($retrieveAnimalsLocationQb->expr()->eq('r.isRvoLeading', 'true'));
      }
      $retrieveAnimalsLocationQb
          ->groupBy('l')
          ->setParameter('minLogDate', $minLogDate->format(SqlUtil::DATE_FORMAT))
      ;

      $qb = $this->getManager()->createQueryBuilder();
      $qb
          ->select('location')
          ->from(Location::class, 'location')
          ->innerJoin('location.company', 'company', Join::WITH, $qb->expr()->eq('location.company', 'company.id'))
          ->where($qb->expr()->eq('company.isActive', 'true'))
          ->andWhere($qb->expr()->eq('location.isActive', 'true'))
          ->andWhere($qb->expr()->notIn('location.id', $retrieveAnimalsLocationQb->getDQL()))
          ->andWhere($qb->expr()->notIn('location.address', $addressQb->getDQL()))
          ->setParameter('minLogDate', $minLogDate->format(SqlUtil::DATE_FORMAT))
      ;

      return $qb->getQuery()->getResult();
  }


    /**
     * @param $location
     * @param string $defaultCountryCode
     * @return null|string
     * @throws \Doctrine\DBAL\DBALException
     */
  public function getCountryCode($location, $defaultCountryCode = Country::NL)
  {
      $locationPrimaryKey = null;
      if ($location instanceof Location && $location->getId()) {
          $locationPrimaryKey = $location->getId();
      } elseif (ctype_digit($location) || is_int($location)) {
          $locationPrimaryKey = intval($location);
      } else {
          return null;
      }

      $sql = "SELECT
          c.code as country_code
        FROM country c
          INNER JOIN address a ON a.country = c.name
          INNER JOIN location l ON l.address_id = a.id
        WHERE l.id = ".$locationPrimaryKey;
      $result = $this->getConnection()->query($sql)->fetch();

      return $result ? $result['country_code'] : $defaultCountryCode;
  }
}