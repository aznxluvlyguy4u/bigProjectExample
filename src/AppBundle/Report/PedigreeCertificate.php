<?php

namespace AppBundle\Report;


use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Animal;
use AppBundle\Entity\BodyFat;
use AppBundle\Entity\Client;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationAddress;
use AppBundle\Entity\MuscleThickness;
use AppBundle\Entity\Ram;
use AppBundle\Entity\TailLength;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;

class PedigreeCertificate
{

    /** @var array */
    private $data;

    /** @var int */
    private $generationOfAscendants;

    /** @var  EntityManager */
    private $em;

    /**
     * PedigreeCertificate constructor.
     * @param EntityManager $em
     * @param Client $client
     * @param Location $location
     * @param Animal $animal
     * @param int $generationOfAscendants
     */
    public function __construct(EntityManager $em, Client $client, Location $location, Animal $animal, $generationOfAscendants = 3)
    {
        $this->em = $em;

        $this->data = array();
        $this->generationOfAscendants = $generationOfAscendants;

        $this->data[ReportLabel::OWNER] = $client;
        $this->data[ReportLabel::OWNER_NAME] = $client->getFirstName() . " " . $client->getLastName();
        $this->data[ReportLabel::ADDRESS] = $location->getCompany()->getAddress();
        $this->data[ReportLabel::UBN] = $location->getUbn();

        //TODO Phase 2: Add breeder information
        $this->data[ReportLabel::BREEDER] = null; //TODO pass Breeder entity
        $this->data[ReportLabel::BREEDER_NAME] = '-'; //TODO
        $this->data[ReportLabel::BREEDER_NAME_CROPPED] = '-'; //TODO incase the format has no space, use this cropped name
        $emptyAddress = new LocationAddress(); //For now an empty Address entity is passed
        $emptyAddress->setStreetName('-');
        $emptyAddress->setAddressNumber('-');
        $emptyAddress->setAddressNumberSuffix('-');
        $emptyAddress->setPostalCode('-');
        $emptyAddress->setCity('-');
        $this->data[ReportLabel::ADDRESS_BREEDER] = $emptyAddress; //TODO pass real Address entity
        $this->data[ReportLabel::BREEDER_NUMBER] = '-000'; //TODO pass real Address entity
//
        $this->data[ReportLabel::ANIMALS][ReportLabel::CHILD][ReportLabel::ENTITY] = $animal;

        $generation = 0;
        $labelAnimal = ReportLabel::CHILD;
        $this->addParents($animal, $labelAnimal, $generation);
        $this->addAnimalValuesToArray(ReportLabel::CHILD, $animal);
    }

    /**
     * Recursively add the previous generations of ascendants.
     *
     * @param Animal $animal
     * @param string $labelAnimal
     * @param int $generation
     */
    private function addParents(Animal $animal = null, $labelAnimal, $generation)
    {
        if($generation < $this->generationOfAscendants) {

            if($animal != null) {
                $father = $animal->getParentFather();
                $mother = $animal->getParentMother();
            } else {
                $father = null;
                $mother = null;
            }

            if($father == null) { $father = new Ram(); }
            if($mother == null) { $mother = new Ewe(); }

            $labelFather = self::getFatherLabel($labelAnimal);
            $labelMother = self::getMotherLabel($labelAnimal);

            $this->data[ReportLabel::ANIMALS][$labelFather][ReportLabel::ENTITY] = $father;
            $this->data[ReportLabel::ANIMALS][$labelMother][ReportLabel::ENTITY] = $mother;
            
            $this->addAnimalValuesToArray($labelFather, $father);
            $this->addAnimalValuesToArray($labelMother, $mother);

            $generation++;

            //Recursive loop for both parents AFTER increasing the generationCount
            $this->addParents($father, $labelFather, $generation);
            $this->addParents($mother, $labelMother, $generation);
        }
    }

    /**
     * @param string $labelAnimal
     * @return string
     */
    public static function getFatherLabel($labelAnimal)
    {
        if($labelAnimal == ReportLabel::CHILD) {
            $labelFather = ReportLabel::FATHER;
        } else {
            $labelFather = $labelAnimal . ReportLabel::_S_FATHER;
        }

        return $labelFather;
    }

    /**
     * @param string $labelAnimal
     * @return string
     */
    public static function getMotherLabel($labelAnimal)
    {
        if($labelAnimal == ReportLabel::CHILD) {
            $labelMother = ReportLabel::MOTHER;
        } else {
            $labelMother = $labelAnimal . ReportLabel::_S_MOTHER;
        }

        return $labelMother;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }


    /**
     * @param string $label
     * @param Animal $animal
     */
    private function addAnimalValuesToArray($label, $animal)
    {
        //Measurement Criteria
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('animal', $animal))
            ->orderBy(['measurementDate' => Criteria::DESC])
            ->setMaxResults(1);

        //Exterior
        $latestExterior = $this->em->getRepository(Exterior::class)
            ->matching($criteria);

        if(sizeof($latestExterior) > 0) {
            $latestExterior = $latestExterior->get(0);
        } else { //create an empty default Exterior
            $latestExterior = new Exterior();
        }

        //MuscleThickness
        $latestMuscleThickness = $this->em->getRepository(MuscleThickness::class)
            ->matching($criteria);

        if(sizeof($latestMuscleThickness) > 0) {
            $latestMuscleThickness = $latestMuscleThickness->get(0);
        } else { //create an empty default Exterior
            $latestMuscleThickness = new MuscleThickness();
        }
        $latestMuscleThickness = $latestMuscleThickness->getMuscleThickness();

        //BodyFat
        $latestBodyFat = $this->em->getRepository(BodyFat::class)
            ->matching($criteria);

        if(sizeof($latestBodyFat) > 0) {
            $latestBodyFat = $latestBodyFat->get(0);
        } else { //create an empty default Exterior
            $latestBodyFat = new BodyFat();
        }
        $latestBodyFat = $latestBodyFat->getFat();

        //TailLength
        $latestTailLength = $this->em->getRepository(TailLength::class)
            ->matching($criteria);

        if(sizeof($latestTailLength) > 0) {
            $latestTailLength = $latestTailLength->get(0);
        } else { //create an empty default Exterior
            $latestTailLength = new TailLength();
        }
        $latestTailLength = $latestTailLength->getLength();

        //Set latest measurement values
        $this->data[ReportLabel::ANIMALS][$label][ReportLabel::LATEST_EXTERIOR] = $latestExterior;
        $this->data[ReportLabel::ANIMALS][$label][ReportLabel::LATEST_MUSCLE_THICKNESS] = $latestMuscleThickness;
        $this->data[ReportLabel::ANIMALS][$label][ReportLabel::LATEST_BODY_FAT] = $latestBodyFat;
        $this->data[ReportLabel::ANIMALS][$label][ReportLabel::LATEST_TAIL_LENGTH] = $latestTailLength;
        
        
        if($animal->getLitter() != null) {
            $litter = $animal->getLitter();
            if($litter->getSize() != null) {
                $litterSize = $litter->getSize();
            } else {
                $litterSize = null;
            }

            if($litter->getLitterGroup() != null) {
                $litterGroup = $litter->getLitterGroup();
            } else {
                $litterGroup = null;
            }
        } else {
            $litterSize = null;
            $litterGroup = null;
        }
        
        $this->data[ReportLabel::ANIMALS][$label][ReportLabel::LITTER_SIZE] = $litterSize;
        $this->data[ReportLabel::ANIMALS][$label][ReportLabel::LITTER_GROUP] = $litterGroup;
        $this->data[ReportLabel::ANIMALS][$label][ReportLabel::LITTER_GROUP] = $litterGroup;
    }

}