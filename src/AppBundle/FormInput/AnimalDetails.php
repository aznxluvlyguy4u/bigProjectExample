<?php

namespace AppBundle\FormInput;

use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Output\AnimalDetailsOutput;
use Doctrine\Common\Collections\ArrayCollection;

class AnimalDetails
{
    /**
     * @param Animal $animal
     * @param ArrayCollection $content
     * @return Ram|Ewe|Neuter
     */
    public static function update($animal, ArrayCollection $content)
    {
        //The ULN should be changed using a separate process, TagReplace (Omnummering).

        //The PedigreeNumber = BreederNumber('A2C4E6')+AnimalOrderNumber+(12345),
        //So it is better to auto-generate it from those two variables and NOT make it editable

        //Edit
        /*
         * genotype
         */
        
        //ExteriorMeasurements (Fokwaarden) are gotten from mixblub and are not editable by the user

        //WeightMeasurements should be created and edited on the separate WeightMeasurement tabs

        return $animal;
    }
}