<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;

/**
 * Class DeclareBirthRepository
 * @package AppBundle\Entity
 */
class DeclareBirthRepository extends BaseRepository {

  public function updateDeclareBirthMessage(DeclareBirth $declareBirthUpdate, $Id) {

    $declareBirth = $this->findOneBy(array (Constant::REQUEST_ID_NAMESPACE => $Id));

    if($declareBirth == null) {
      return null;
    }

    //TODO Verify if this works
    $ulnCountryCode = $declareBirthUpdate->getAnimal()->getUlnCountryCode();
    if ($ulnCountryCode != null) {
      $declareBirth->getAnimal()->setUlnCountryCode($ulnCountryCode);
    }

    $ulnNumber = $declareBirthUpdate->getAnimal()->getUlnNumber();
    if ($ulnNumber != null) {
      $declareBirth->getAnimal()->setUlnNumber($ulnNumber);
    }

    $pedigreeCountryCode = $declareBirthUpdate->getAnimal()->getPedigreeCountryCode();
    if ($pedigreeCountryCode != null) {
      $declareBirth->getAnimal()->setPedigreeCountryCode($pedigreeCountryCode);
    }

    $pedigreeNumber = $declareBirthUpdate->getAnimal()->getPedigreeNumber();
    if ($pedigreeNumber != null) {
      $declareBirth->getAnimal()->setPedigreeNumber($pedigreeNumber);
    }

    //TODO: How to update gender? Create a new Ram/Ewe? Check IRSerializer->parseDeclareBirth

    if ($declareBirthUpdate->getBirthType() != null) {
      $declareBirth->setBirthType($declareBirthUpdate->getBirthType());
    }

    if ($declareBirthUpdate->getDateOfBirth() != null) {
      $declareBirth->setDateOfBirth($declareBirthUpdate->getDateOfBirth());
    }


    if ($declareBirthUpdate->getHasLambar() != null) {
      $declareBirth->setIsLambar($declareBirthUpdate->getHasLambar());
    }

    if ($declareBirthUpdate->getIsAborted() != null) {
      $declareBirth->setIsAborted($declareBirthUpdate->getIsAborted());
    }

    if ($declareBirthUpdate->getIsPseudoPregnancy() != null) {
      $declareBirth->setIsPseudoPregnancy($declareBirthUpdate->getIsPseudoPregnancy());
    }

    if ($declareBirthUpdate->getLitterSize() != null) {
      $declareBirth->setLitterSize($declareBirthUpdate->getLitterSize());
    }

    if ($declareBirthUpdate->getBirthWeight() != null) {
      $declareBirth->setBirthWeight($declareBirthUpdate->getBirthWeight());
    }

    if ($declareBirthUpdate->getBirthTailLength() != null) {
      $declareBirth->setBirthTailLength($declareBirthUpdate->getBirthTailLength());
    }

    return $this->update($declareBirth);
  }
}