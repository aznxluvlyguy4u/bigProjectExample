<?php

namespace AppBundle\Component\Modifier;


use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Common\Persistence\ObjectManager;

class AnimalRemover extends MessageModifier
{

    public function __construct(ObjectManager $em)
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
     * @param DeclareBirth $declareBirthObject
     * @param $doctrine
     * @return DeclareBirth
     */
    public static function removeChildFromDeclareBirth($declareBirthObject, $doctrine)
    {
        $child = $declareBirthObject->getAnimal();

        if($child != null) {
            $retrievedAnimal = self::retrieveAnimalByAnimalObject($child, $doctrine);
            if($retrievedAnimal != null){
                $doctrine->getManager()->remove($retrievedAnimal);
            }

            $declareBirthObject->setAnimal(null);
        }

        return $declareBirthObject;
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