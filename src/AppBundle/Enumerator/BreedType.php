<?php

namespace AppBundle\Enumerator;

use AppBundle\Util\Translation;

class BreedType
{
  const BLIND_FACTOR = "BLIND_FACTOR";
  const MEAT_LAMB_FATHER = "MEAT_LAMB_FATHER";
  const MEAT_LAMB_MOTHER = "MEAT_LAMB_MOTHER";
  const PARENT_ANIMAL = "PARENT_ANIMAL";
  const PURE_BRED = "PURE_BRED";
  const REGISTER = "REGISTER";
  const SECONDARY_REGISTER = "SECONDARY_REGISTER";
  const UNDETERMINED = "UNDETERMINED";
  const EN_MANAGEMENT = "EN_MANAGEMENT";
  const EN_BASIS = "EN_BASIS";

  /**
   * @return array
   */
  public static function getAll()
  {
    return [
      self::BLIND_FACTOR => self::BLIND_FACTOR,
      self::MEAT_LAMB_FATHER => self::MEAT_LAMB_FATHER,
      self::MEAT_LAMB_MOTHER => self::MEAT_LAMB_MOTHER,
      self::PARENT_ANIMAL => self::PARENT_ANIMAL,
      self::PURE_BRED => self::PURE_BRED,
      self::REGISTER => self::REGISTER,
      self::SECONDARY_REGISTER => self::SECONDARY_REGISTER,
      self::UNDETERMINED => self::UNDETERMINED,
      self::EN_MANAGEMENT => self::EN_MANAGEMENT,
      self::EN_BASIS => self::EN_BASIS,
    ];
  }


  /**
   * @return array
   */
  public static function getAllInDutch()
  {
    $results = [];
    foreach (self::getAll() as $key => $item) {
      $results[$key] = Translation::getDutch($item);
    }
    return $results;
  }
}