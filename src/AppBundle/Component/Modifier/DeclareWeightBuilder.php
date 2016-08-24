<?php

namespace AppBundle\Component\Modifier;


use AppBundle\Component\NsfoBaseBuilder;
use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareWeight;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\RequestStateType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

class DeclareWeightBuilder extends NsfoBaseBuilder
{

    /**
     * @param ObjectManager $manager
     * @param ArrayCollection $content
     * @param Client $client
     * @param Person $loggedInUser
     * @param Location $location
     * @return DeclareWeight
     */
    public static function post(ObjectManager $manager, ArrayCollection $content, Client $client, Person $loggedInUser, Location $location)
    {
        $declareWeight = new DeclareWeight();
        $declareWeight = self::postBase($client, $loggedInUser, $location, $declareWeight);
        $declareWeight = self::setDeclareWeightValues($manager, $content, $location, $declareWeight);

        return $declareWeight;
    }

    /**
     * @param ObjectManager $manager
     * @param ArrayCollection $content
     * @param Location $location
     * @param DeclareWeight $declareWeight
     * @return DeclareWeight
     */
    private static function setDeclareWeightValues(ObjectManager $manager, ArrayCollection $content, Location $location, DeclareWeight $declareWeight)
    {
        /* Set the RequestState to OPEN since it needs to be approved by the third party */
        $declareWeight->setRequestState(RequestStateType::FINISHED);

        /* Set non-Animal values */

        $measurementDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::MEASUREMENT_DATE, $content);
        $weight = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::WEIGHT, $content);

        $declareWeight->setMeasurementDate($measurementDate);
        $declareWeight->setWeight($weight);

        $declareWeight->setLocation($location);

        /* Set Animal values */

        $animalArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::ANIMAL, $content);

        /** @var AnimalRepository $animalRepository */
        $animalRepository = $manager->getRepository(Animal::class);

        $animal = $animalRepository->findAnimalByAnimalArray($animalArray);
        $declareWeight->setAnimal($animal);

        return $declareWeight;
    }
}