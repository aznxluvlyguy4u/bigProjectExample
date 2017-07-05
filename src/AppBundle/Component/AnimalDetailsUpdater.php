<?php


namespace AppBundle\Component;


use AppBundle\Cache\GeneDiversityUpdater;
use AppBundle\Entity\Animal;
use AppBundle\Util\ArrayUtil;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;

class AnimalDetailsUpdater
{
    /**
     * @param ObjectManager $em
     * @param Animal $animal
     * @param Collection $content
     * @return Animal
     */
    public static function update(ObjectManager $em, $animal, Collection $content)
    {
        if(!($animal instanceof Animal)){ return $animal; }

        /** @var Connection $conn */
        $conn = $em->getConnection();

        //Keep track if any changes were made
        $anyValueWasUpdated = false;

        //Collar color & number
        if($content->containsKey('collar')) {
            $collar = $content->get('collar');
            $newCollarNumber = ArrayUtil::get('number',$collar);
            $newCollarColor = ArrayUtil::get('color',$collar);

            if($animal->getCollarNumber() != $newCollarNumber) {
                $animal->setCollarNumber($newCollarNumber);
                $anyValueWasUpdated = true;
            }

            if($animal->getCollarColor() != $newCollarColor) {
                $animal->setCollarColor($newCollarColor);
                $anyValueWasUpdated = true;
            }
        }

        //Only update animal in database if any values were actually updated
        if($anyValueWasUpdated) {
            $em->persist($animal);
            $em->flush();
        }

        //TODO if breedCode was updated toggle $isBreedCodeUpdated boolean to true
        $isBreedCodeUpdated = false;
        if($isBreedCodeUpdated) {
            //Update heterosis and recombination values of parent and children if breedCode of parent was changed
            GeneDiversityUpdater::updateByParentId($conn, $animal->getId());
        }

        return $animal;
    }
}