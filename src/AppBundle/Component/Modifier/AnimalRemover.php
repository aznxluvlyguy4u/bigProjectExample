<?php

namespace AppBundle\Component\Modifier;


use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;

class AnimalRemover extends MessageModifier
{

    public function __construct(ObjectManager $em)
    {
        parent::__construct($em);
    }

    public static function removeUnverifiedAnimalFromMessageObject($messageObject, $em)
    {
        $animal = $messageObject->getAnimal();
        if($animal != null) {
            $retrievedAnimal = self::retrieveAnimalByAnimalObject($animal, $em);
            if($retrievedAnimal == null){ $messageObject->setAnimal(null); }
        }

        return $messageObject;
    }

    /**
     * @param DeclareBirth $declareBirthObject
     * @param EntityManagerInterface $em
     * @return DeclareBirth
     */
    public static function removeChildFromDeclareBirth($declareBirthObject, $em)
    {
        $child = $declareBirthObject->getAnimal();

        if($child != null) {
            $retrievedAnimal = self::retrieveAnimalByAnimalObject($child, $em);
            if($retrievedAnimal != null){
                $em->remove($retrievedAnimal);
            }

            $declareBirthObject->setAnimal(null);
        }

        return $declareBirthObject;
    }

    /**
     * @param EntityManagerInterface $em
     * @param DeclareBirth $messageObject
     * @return DeclareBirth
     */
    public static function removeUnverifiedSurrogateFromMessageObject($messageObject, $em)
    {
        //TODO Verify if surrogate can be from an unverified ULN or is always from a location registered by the Client
        return $messageObject;
    }

    /**
     * @param EntityManagerInterface $em
     * @param Animal|Ram|Ewe|Neuter $animal
     * @return \AppBundle\Entity\Animal|null
     */
    private static function retrieveAnimalByAnimalObject($animal, $em)
    {
        $animalRepository = $em->getRepository(Animal::class);
        return $animalRepository->findByAnimal($animal);
    }
}