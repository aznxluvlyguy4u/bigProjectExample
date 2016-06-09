<?php

namespace AppBundle\Entity;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\TagStateType;
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
    if(!Utils::verifyUlnFormat($ulnString)) {
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
   * @return ArrayCollection
   */
  public function findTags(Client $client, $tagStatus = TagStateType::UNASSIGNED)
  {
    $tags = new ArrayCollection();

    foreach($client->getTags() as $tag){
      if($tag->getTagStatus() == $tagStatus) {
        $tags->add($tag);
      }
    }

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
   * @param Ram|Ewe|Neuter $animal
   * @return Tag|null
   */
  public function findByAnimal($client, $animal)
  {
       return $this->findOneByUln($client, $animal->getUlnCountryCode(), $animal->getUlnNumber());
  }


}