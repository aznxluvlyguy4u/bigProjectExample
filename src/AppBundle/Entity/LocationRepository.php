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
   * @return ArrayCollection
   */
  public function findByUser($user)
  {
    //Result set
    $locations = new ArrayCollection();

    //Get companies of user
    $companies = $user->getCompanies();

    foreach($companies as $company) {
      //Get locations of every company, add it to result set
      $locationsRetrieved = $company->getLocations();

      foreach($locationsRetrieved as $location) {
        $locations->add($location);
      }

    }

    return $locations;
  }

  /**
   * @param $ubn
   * @return null|object
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