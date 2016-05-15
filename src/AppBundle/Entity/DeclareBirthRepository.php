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

    if ($declareBirthUpdate->getLambar() != null) {
      $declareBirth->setLambar($declareBirthUpdate->getLambar());
    }

    if ($declareBirthUpdate->getAborted() != null) {
      $declareBirth->setAborted($declareBirthUpdate->getAborted());
    }

    if ($declareBirthUpdate->getPseudoPregnancy() != null) {
      $declareBirth->setPseudoPregnancy($declareBirthUpdate->getPseudoPregnancy());
    }

    if ($declareBirthUpdate->getLitterSize() != null) {
      $declareBirth->setLitterSize($declareBirthUpdate->getLitterSize());
    }

    if ($declareBirthUpdate->getAnimalWeight() != null) {
      $declareBirth->setAnimalWeight($declareBirthUpdate->getAnimalWeight());
    }

    if ($declareBirthUpdate->getBirthTailLength() != null) {
      $declareBirth->setBirthTailLength($declareBirthUpdate->getBirthTailLength());
    }

    return $this->update($declareBirth);
  }
}