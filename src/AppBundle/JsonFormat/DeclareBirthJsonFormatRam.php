<?php

namespace AppBundle\JsonFormat;


use AppBundle\Entity\Ram;

/**
 * Class DeclareBirthJsonFormatFather
 * @package AppBundle\JsonFormat
 */
class DeclareBirthJsonFormatRam extends JsonFormatAnimalBase
{
    /**
     * @param Ram $ram
     */
    public function setRamUln(Ram $ram)
    {
        $this->setAnimalUln($ram);
    }

    /**
     * @param Ram $ram
     */
    public function setRamPedigree(Ram $ram)
    {
        $this->setAnimalPedigree($ram);
    }

    /**
     * @param Ram $ram
     */
    public function setRamUlnAndPedigree(Ram $ram)
    {
        $this->setAnimalUlnAndPedigree($ram);
    }
}