<?php

namespace AppBundle\Enumerator;

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
    static function getConstants() {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
}