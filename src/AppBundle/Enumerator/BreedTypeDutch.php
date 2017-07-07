<?php

namespace AppBundle\Enumerator;

class BreedTypeDutch
{
  const BLIND_FACTOR = "BLINDFACTOR";
  const MEAT_LAMB_FATHER = "VLEESLAMVADERDIER";
  const MEAT_LAMB_MOTHER = "VLEESLAMMOEDERDIER";
  const PARENT_ANIMAL = "OUDERDIER";
  const PURE_BRED = "VOLBLOED";
  const REGISTER = "REGISTER";
  const SECONDARY_REGISTER = "HULPBOEK";
  const UNDETERMINED = "ONBEPAALD";
  const EN_MANAGEMENT = "EN-MANAGEMENT";
  const EN_BASIS = "EN-BASIS";

    /**
     * @return array
     */
    static function getConstants() {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
}