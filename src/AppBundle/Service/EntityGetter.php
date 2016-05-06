<?php

namespace AppBundle\Service;

use AppBundle\Entity\Neuter;
use AppBundle\Enumerator\AnimalType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\CssSelector\Node\NegationNode;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

class EntityGetter
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    public function retrieveAnimal($animal)
    {
        //By default just return the original animal
        $retrievedAnimal = $animal;

        //The front-end should give at least a uln or pedigree number + country code combination
        if(array_key_exists('uln_number', $animal) && array_key_exists('uln_country_code', $animal)){

            $ulnNumber = $animal['uln_number'];
            $ulnCountryCode = $animal['uln_country_code'];

            $filterArray = array("ulnNumber" => $ulnNumber, "ulnCountryCode" => $ulnCountryCode);
            $retrievedAnimal = $this->entityManager->getRepository('AppBundle:Animal')->findOneBy($filterArray);

            if($retrievedAnimal == null) {
                $retrievedAnimal = $this->createANewNeuter($animal);
            }

        } else if (array_key_exists('pedigree_number', $animal) && array_key_exists('pedigree_country_code', $animal)){

            $pedigreeNumber = $animal['pedigree_number'];
            $pedigreeCountryCode = $animal['pedigree_country_code'];

            $filterArray = array("pedigreeNumber" => $pedigreeNumber, "pedigreeCountryCode" => $pedigreeCountryCode);
            $retrievedAnimal = $this->entityManager->getRepository('AppBundle:Animal')->findOneBy($filterArray);

            if($retrievedAnimal == null) {
                $retrievedAnimal = $this->createANewNeuter($animal);
            }
        }

        return $retrievedAnimal;
    }

    private function createANewNeuter($animal)
    {
        $neuter = new Neuter();

        if (array_key_exists('uln_number', $animal)) {
            $neuter->setUlnNumber($animal['uln_number']); }

        if (array_key_exists('uln_country_code', $animal)) {
            $neuter->setUlnCountryCode($animal['uln_country_code']); }

        if (array_key_exists('pedigree_number', $animal)) {
            $neuter->setPedigreeNumber($animal['pedigree_number']); }

        if (array_key_exists('pedigree_country_code', $animal)) {
            $neuter->setPedigreeCountryCode($animal['pedigree_country_code']); }

        if (array_key_exists('name', $animal)) {
            $neuter->setName($animal['name']); }

        //FIXME: if dateOfBirth is refactored out of Animal into the message, delete this
        if (array_key_exists('date_of_birth', $animal)) { 
            $neuter->setDateOfBirth($animal['date_of_birth']); }

        //FIXME: if dateOfDeath is refactored out of Animal into the message, delete this
        if (array_key_exists('date_of_death', $animal)) {
            $neuter->setDateOfDeath($animal['date_of_death']); }

        if (array_key_exists('gender', $animal)) {
            $neuter->setGender($animal['gender']); }

        if (array_key_exists('parent_father', $animal)) {
            $neuter->setParentFather($animal['parent_father']); }

        if (array_key_exists('parent_mother', $animal)) {
            $neuter->setParentMother($animal['parent_mother']); }

        if (array_key_exists('parent_neuter', $animal)) {
            $neuter->setParentNeuter($animal['parent_neuter']); }

        if (array_key_exists('animal_type', $animal)) {
            $neuter->setAnimalType($animal['animal_type']); }

        if (array_key_exists('animal_category', $animal)) {
            $neuter->setAnimalCategory($animal['animal_category']); }

        if (array_key_exists('animal_working_number', $animal)) {
            $neuter->setAnimalWorkingNumber($animal['animal_working_number']); }

        if (array_key_exists('animal_hair_colour', $animal)) {
            $neuter->setAnimalHairColour($animal['animal_hair_colour']); }

        //Note a newly created neuter cannot have any messages yet,
        //(like arrivals, departures, imports)
        //so it is not necessary to map them here.
        //TODO Check again for all messages if this is correct: A newly created neuter is also not created with any children.


        $neuter->setAnimalType(AnimalType::sheep);

        //Persist the new neuter
        $this->entityManager->persist($neuter);
        $this->entityManager->flush();

        return $neuter;
    }

}