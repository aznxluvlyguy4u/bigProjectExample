<?php

namespace AppBundle\Service;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Enumerator\RequestType;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class AnimalLocationHistoryService
 * @package AppBundle\Service
 */
class AnimalLocationHistoryService
{
    /**
     * @var ObjectManager
     */
    private static $entityManager;

    public function __construct($entityManager)
    {
        self::$entityManager = $entityManager;
    }


    /**
     * TODO An assumption is made that edits are done on the last state of the animal and not 'in the middle' of their history.
     *
     * @param DeclareArrival|DeclareImport $messageObject
     * @return AnimalResidence
     */
    public static function logAnimalResidenceInEdit($messageObject) {

        $animal = $messageObject->getAnimal();
        $animalResidence = Utils::returnLastItemFromCollectionByLogDate($animal->getAnimalResidenceHistory());
        $animalResidence->setIsPending(true);

        //The Date has to be set by the worker

        self::$entityManager->persist($animalResidence);
        self::$entityManager->flush();

        return $animalResidence;
    }

    /**
     * TODO An assumption is made that edits are done on the last state of the animal and not 'in the middle' of their history
     *
     * @param DeclareExport|DeclareDepart $messageObject
     * @return AnimalResidence
     */
    public static function logAnimalResidenceOut($messageObject) {

        $animal = $messageObject->getAnimal();
        $location = $messageObject->getLocation();

        $animalResidence = Utils::returnLastItemFromCollectionByLogDate($animal->getAnimalResidenceHistory());

        if($animalResidence == null) {
            $animalResidence = new AnimalResidence($location->getCountryCode(),true);
            self::$entityManager->persist($animalResidence);
            self::$entityManager->flush();
            $animalResidence->setAnimal($animal);
            $animalResidence->setLocation($location);

            self::$entityManager->persist($animalResidence);
            self::$entityManager->flush();
        }

        //The Date has to be set by the worker

        return $animalResidence;
    }

}