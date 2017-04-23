<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;
use Doctrine\Common\Collections\ArrayCollection;

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
}