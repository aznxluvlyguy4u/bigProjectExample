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

        $animal->setDateOfBirth(new \DateTime($content->get(Constant::DATE_OF_BIRTH_NAMESPACE)));

        return $animal;
    }
}