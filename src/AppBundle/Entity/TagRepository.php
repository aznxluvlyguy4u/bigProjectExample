<?php

namespace AppBundle\Entity;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Validation\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\ClickableInterface;

class TagRepository extends BaseRepository {

  /**
   * @param Client $client
   * @param string $ulnString
   * @return Tag|null
   */
  public function findOneByString(Client $client, $ulnString)
  {
    //Verify format first
    if(!Validator::verifyUlnFormat($ulnString)) {
      return null;
    }
    $uln = Utils::getUlnFromString($ulnString);

    return $this->findOneByUln($client, $uln[Constant::ULN_COUNTRY_CODE_NAMESPACE], $uln[Constant::ULN_NUMBER_NAMESPACE]);
  }

  /**
   * @param Client $client
   * @param $countryCode
   * @param $ulnNumber
   * @return Tag|null
   */
  public function findOneByUln(Client $client, $countryCode, $ulnNumber)
  {
    foreach($client->getTags() as $tag){
      if($tag->getUlnCountryCode() == $countryCode && $tag->getUlnNumber() == $ulnNumber) {
        return $tag; //assuming uln is unique
      }
    }
    return null;
  }


  /**
   * @param $ulnCountryCode
   * @param $ulnNumber
   * @return ArrayCollection|object
   */
  public function findByUlnNumberAndCountryCode($ulnCountryCode, $ulnNumber)
  {
    return $this->findOneBy(array('ulnCountryCode'=>$ulnCountryCode, 'ulnNumber'=>$ulnNumber));
  }

  /**
   * @param Client $client
   * @param string $tagStatus
   * @return array
   */
  public function findTags(Client $client, Location $location, $tagStatus = TagStateType::UNASSIGNED)
  {
    if($client == null || $location == null) { return []; }

    $sql = "SELECT tag_status, animal_order_number, order_date, uln_country_code, uln_number 
            FROM tag WHERE owner_id = ".$client->getId()." AND location_id = ".$location->getId()."  AND tag_status = '".$tagStatus."'";
    $tags = $this->getManager()->getConnection()->query($sql)->fetchAll();
    
    return $tags;
  }

  /**
   * @param Client $client
   * @param int $count
   * @return Tag|ArrayCollection
   */
  public function findUnAssignedTags(Client $client, $count)
  {
    $tags = new ArrayCollection();

    $i = 0;

    foreach($client->getTags() as $tag){
      if($tag->getTagStatus() == TagStateType::UNASSIGNED) {
        $tags->add($tag);
        $i = $i + 1;
      }
      if($i = $count) { break; }
    }

    if(sizeof($tags)==1) {
      return $tags->get(0);
    } else {
      return $tags;
    }
  }

  /**
   * @param Client $client
   * @param string $ulnNumber
   * @param Location $location
   * @return bool
   */
  public function isAnUnassignedTag(Client $client, Location $location, $ulnNumber)
  {
    if($client == null || $location == null || $ulnNumber == null) { return false; }
    if(!is_int($client->getId()) || !is_int($location->getId()) ) { return false; }

    $tagStatus = TagStateType::UNASSIGNED;
    $sql = "SELECT COUNT(*)
            FROM tag WHERE owner_id = ".$client->getId()." AND location_id = ".$location->getId()."  AND tag_status = '".$tagStatus."'  AND uln_number = '".$ulnNumber."'";
    return $this->getManager()->getConnection()->query($sql)->fetch()['count'] > 0 ? true : false;
  }

  /**
   * @param Client $client
   * @param Ram|Ewe|Neuter $animal
   * @return Tag|null
   */
  public function findByAnimal($client, $animal)
  {
       return $this->findOneByUln($client, $animal->getUlnCountryCode(), $animal->getUlnNumber());
  }


  /**
   * @param Client $client
   * @param Location $location
   * @return int
   */
  public function getUnassignedTagCount(Client $client, Location $location) {
    if($client == null || $location == null) { return 0; }
    if(!is_int($client->getId()) || !is_int($location->getId()) ) { return false; }

    $tagStatus = TagStateType::UNASSIGNED;
    $sql = "SELECT COUNT(*)
            FROM tag WHERE owner_id = ".$client->getId()." AND location_id = ".$location->getId()."  AND tag_status = '".$tagStatus."'";
    return $this->getManager()->getConnection()->query($sql)->fetch()['count'];
  }

}