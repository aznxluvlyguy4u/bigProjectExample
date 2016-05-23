<?php

namespace AppBundle\Component\Modifier;


use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use Doctrine\ORM\EntityManager;

class AnimalRemover extends MessageModifier
{

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    public static function removeUnverifiedAnimalFromMessageObject($messageObject, $doctrine)
    {
        $animal = $messageObject->getAnimal();
        if($animal != null) {
            $retrievedAnimal = self::retrieveAnimalByAnimalObject($animal, $doctrine);
            if($retrievedAnimal == null){ $messageObject->setAnimal(null); }
        }

        return $messageObject;
    }

    /**
     * @param DeclareBirth $messageObject
     * @return DeclareBirth
     */
    public static function removeUnverifiedFatherFromMessageObject($messageObject, $doctrine)
    {
        //TODO Father can be animal not registered at NSFO
        return $messageObject;
    }

    /**
     * @param DeclareBirth $messageObject
     * @return DeclareBirth
     */
    public static function removeUnverifiedSurrogateFromMessageObject($messageObject, $doctrine)
    {
        //TODO Verify if surrogate can be from an unverified ULN or is always from a location registered by the Client
        return $messageObject;
    }

    /**
     * @param Animal|Ram|Ewe|Neuter $animal
     * @return \AppBundle\Entity\Animal|null
     */
    private static function retrieveAnimalByAnimalObject($animal, $doctrine)
    {
        $animalRepository = $doctrine->getRepository(Constant::ANIMAL_REPOSITORY);
        return $animalRepository->findByAnimal($animal);
    }
}