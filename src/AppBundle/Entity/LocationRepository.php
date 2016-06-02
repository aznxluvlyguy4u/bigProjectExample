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
  public function findByUbn($ubn)
  {
    return $this->findOneBy(array(Constant::UBN_NAMESPACE => $ubn));
  }

  /**
   * @param array $location
   * @return null|object
   */
  public function findByLocationArray(array $location)
  {
    return $this->findByUbn($location[Constant::UBN_NAMESPACE]);
  }
}