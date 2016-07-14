<?php

namespace AppBundle\Report;


use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Ram;

class PedigreeCertificate
{

    /** @var array */
    private $data;

    /** @var int */
    private $generationOfAscendants;

    /**
     * PedigreeCertificate constructor.
     * @param Client $client
     * @param Location $location
     * @param Animal $animal
     * @param int $generationOfAscendants
     */
    public function __construct(Client $client, Location $location, Animal $animal, $generationOfAscendants = 3)
    {
        $this->data = array();
        $this->generationOfAscendants = $generationOfAscendants;

        $this->data[ReportLabel::OWNER] = $client;
        $this->data[ReportLabel::OWNER_NAME] = $client->getFirstName() . " " . $client->getLastName();
        $this->data[ReportLabel::ADDRESS] = $location->getCompany()->getAddress();
        $this->data[ReportLabel::UBN] = $location->getUbn();

        //TODO Add breeder information
//            $this->data[ReportLabel::BREEDER] = $breeder;
        $this->data[ReportLabel::BREEDER_NAME] = '-'; //TODO
//        $this->data[ReportLabel::ADDRESS_BREEDER] = $address; //TODO

        $this->data[ReportLabel::ANIMALS][ReportLabel::CHILD] = $animal;

        $generation = 0;
        $labelAnimal = ReportLabel::CHILD;
        $this->addParents($animal, $labelAnimal, $generation);
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

            $this->data[ReportLabel::ANIMALS][$labelFather] = $father;
            $this->data[ReportLabel::ANIMALS][$labelMother] = $mother;

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



}