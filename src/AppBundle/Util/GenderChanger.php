<?php

namespace AppBundle\Util;


use AppBundle\Entity\Animal;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\GenderHistoryItem;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\GenderType;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class GenderChanger
 * @package AppBundle\Util
 */
class GenderChanger
{

    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * GenderChanger constructor.
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param Animal $animal
     * @return bool
     */
    public function hasDirectChildRelationshipCheck($animal)
    {
        if($animal instanceof Ewe || $animal instanceof Ram || $animal instanceof Neuter) {
            return $animal->getChildren()->count() > 0;
        } else {
            return false;
        }
    }

    /**
     * @param Animal $animal
     * @return Ewe|null
     */
    public function makeFemale($animal)
    {
        if($animal instanceof Ewe) {
            return $animal;

        } elseif ($animal instanceof Ram || $animal instanceof Neuter) {
            
            $ewe = new Ewe();
            $ewe->duplicateValuesAndTransferRelationships($animal);

            $previousGender = StringUtil::getGenderFullyWritten($animal->getGender());
            $newGender = GenderType::FEMALE;
            $genderHistoryItem = new GenderHistoryItem($previousGender, $newGender);
            $genderHistoryItem->setAnimal($ewe);
            $ewe->addGenderHistoryItem($genderHistoryItem);

            $this->manager->persist($ewe);
            $this->manager->persist($genderHistoryItem);
            $this->manager->remove($animal);
            $this->manager->flush();

        } else {
            return null;
        }
    }


    /**
     * @param Animal $animal
     * @return Ram|null
     */
    public function makeMale($animal)
    {
        if($animal instanceof Ram) {
            return $animal;

        } elseif ($animal instanceof Ewe || $animal instanceof Neuter) {

            $ram = new Ram();
            $ram->duplicateValuesAndTransferRelationships($animal);

            $previousGender = StringUtil::getGenderFullyWritten($animal->getGender());
            $newGender = GenderType::MALE;
            $genderHistoryItem = new GenderHistoryItem($previousGender, $newGender);
            $genderHistoryItem->setAnimal($ram);
            $ram->addGenderHistoryItem($genderHistoryItem);

            $this->manager->persist($ram);
            $this->manager->persist($genderHistoryItem);
            $this->manager->remove($animal);
            $this->manager->flush();

        } else {
            return null;
        }
    }
    
}