<?php

namespace AppBundle\FormInput;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\WeightMeasurement;
use AppBundle\Util\Finder;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;

class WeightMeasurements
{
    /**
     * @param Collection $content
     * @param array $animals
     * @param ObjectManager $manager
     * @return array
     */
    public static function createAndPersist(Collection $content, $animals, ObjectManager $manager)
    {
        $weightMeasurementObjects = array();
        $measurements = $content->get(JsonInputConstant::WEIGHT_MEASUREMENTS);
        $dateOfMeasurement = $content->get(JsonInputConstant::DATE_OF_MEASUREMENT);

        $updatedAnimals = array();

        foreach($measurements as $measurement)
        {
            $animal = Finder::findAnimalByUlnValues($animals,
                $measurement[Constant::ULN_COUNTRY_CODE_NAMESPACE],
                $measurement[Constant::ULN_NUMBER_NAMESPACE]);

            if($animal != null) { //null should actually not be possible, because the uln values in frontend are gotten from the returned livestock

                $weightMeasurementObject = new WeightMeasurement();

                $weightMeasurementObject->setAnimal($animal);
                $weightMeasurementObject->setLogDate(new \DateTime('now'));
                $weightMeasurementObject->setWeight($measurement[JsonInputConstant::WEIGHT]);
                $weightMeasurementObject->setWeightMeasurementDate(new \DateTime($dateOfMeasurement));

                $animal->addWeightMeasurement($weightMeasurementObject);

                //Add new and updated objects to arrays
                $weightMeasurementObjects[] = $weightMeasurementObject;
                $updatedAnimals[] = $animal;

                //persist changes
                $manager->persist($weightMeasurementObject);
                $manager->persist($animal);
                $manager->flush();
            }
        }

        return array(JsonInputConstant::WEIGHT_MEASUREMENTS => $weightMeasurementObjects,
                                Constant::ANIMALS_NAMESPACE => $updatedAnimals);
    }

}