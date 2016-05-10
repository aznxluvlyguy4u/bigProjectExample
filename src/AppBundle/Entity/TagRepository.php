<?php

namespace AppBundle\Entity;

class TagRepository extends BaseRepository {

  /**
   * @param $ulnCountryCode
   * @param $ulnNumber
   * @return array|null
   */
  public function findByUlnNumberAndCountryCode($ulnCountryCode, $ulnNumber)
  {
    return $this->findOneBy(array('ulnCountryCode'=>$ulnCountryCode, 'ulnNumber'=>$ulnNumber));
  }
}