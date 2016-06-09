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
        //The ULN should be changed using a separate process. Not though updating the AnimalDetails.

        //TODO Update Animal Details here, but don't persist them yet. Persist in the controller (?)

        $animal->setPedigreeCountryCode($content->get(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE));
        $animal->setPedigreeNumber($content->get(Constant::PEDIGREE_NUMBER_NAMESPACE));
        $animal->setAnimalOrderNumber($content->get(JsonInputConstant::WORK_NUMBER));

        return $animal;
    }
}