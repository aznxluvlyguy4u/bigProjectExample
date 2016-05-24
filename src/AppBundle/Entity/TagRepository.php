<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\TagStateType;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\ClickableInterface;

class TagRepository extends BaseRepository {

  /**
   * validate if Id is of format: AZ123456789
   *
   * @param $ulnString
   * @return bool
   */
  public function verifyUlnFormat($ulnString) {
    if(preg_match("([A-Z]{2}\d+)",$ulnString)) {
      return true;
    }
    return false;
  }

  /**
   * @param Client $client
   * @param string $ulnString
   * @return Tag|null
   */
  public function findOneByString(Client $client, $ulnString)
  {
    //Verify format first
    if(!$this->verifyUlnFormat($ulnString)) {
      return null;
    }
    $countryCode = mb_substr($ulnString, 0, 2, 'utf-8');
    $ulnNumber = mb_substr($ulnString, 2, strlen($ulnString));

    return $this->findOneByUln($client, $countryCode, $ulnNumber);
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



}