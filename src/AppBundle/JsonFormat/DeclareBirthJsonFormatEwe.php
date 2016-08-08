<?php

namespace AppBundle\JsonFormat;


use AppBundle\Entity\Ewe;

/**
 * Class DeclareBirthJsonFormatEwe
 * @package AppBundle\JsonFormat
 */
class DeclareBirthJsonFormatEwe extends JsonFormatAnimalBase
{

    /**
     * @param Ewe $ewe
     */
    public function setEweUln(Ewe $ewe)
    {
        $this->setAnimalUln($ewe);
    }

    /**
     * @param Ewe $ewe
     */
    public function setEwePedigree(Ewe $ewe)
    {
        $this->setAnimalPedigree($ewe);
    }

    /**
     * @param Ewe $ewe
     */
    public function setEweUlnAndPedigree(Ewe $ewe)
    {
        $this->setAnimalUlnAndPedigree($ewe);
    }
}