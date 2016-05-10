<?php

namespace AppBundle\Service;

use AppBundle\Constant\Constant;
use \AppBundle\Entity\Ewe;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Tag;
use AppBundle\Enumerator\AnimalType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\DateTime;

class EntityGetter
{

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * EntityGetter constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param $ulnCountryCode
     * @param $ulnNumber
     * @return array|null
     */
    public function retrieveTag($ulnCountryCode, $ulnNumber)
    {

        $tagRepository = $this->entityManager->getRepository(Constant::TAG_REPOSITORY);
        return $tagRepository->findByUlnNumberAndCountryCode($ulnCountryCode, $ulnNumber);
    }

    /**
     * @param ArrayCollection $declareArrayContent
     * @return Neuter|array|null
     */
    public function retrieveAnimal($declareArrayContent)
    {
        //By default just return the original animal
        $retrievedAnimal = $declareArrayContent->get('animal');

        $animalRepository = $this->entityManager->getRepository(Constant::ANIMAL_REPOSITORY);

        //At least a uln or pedigree number + country code combination must be given to find an Animal
        if(array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $retrievedAnimal) && array_key_exists(Constant::ULN_COUNTRY_CODE_NAMESPACE, $retrievedAnimal)){
            $ulnNumber = $retrievedAnimal[Constant::ULN_NUMBER_NAMESPACE];
            $ulnCountryCode = $retrievedAnimal[Constant::ULN_COUNTRY_CODE_NAMESPACE];
            $retrievedAnimal = $animalRepository->findByCountryCodeAndUlnOrPedigree($ulnCountryCode, $ulnNumber);

            if($retrievedAnimal == null) {
                $retrievedAnimal = $this->createNewAnimal($declareArrayContent);
            }

        } else if (array_key_exists(Constant::PEDIGREE_NUMBER_NAMESPACE, $retrievedAnimal) && array_key_exists(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE, $retrievedAnimal)){
            $pedigreeNumber = $retrievedAnimal[Constant::PEDIGREE_NUMBER_NAMESPACE];
            $pedigreeCountryCode = $retrievedAnimal[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE];
            $retrievedAnimal = $animalRepository->findByCountryCodeAndUlnOrPedigree($pedigreeCountryCode, $pedigreeNumber);

            if($retrievedAnimal == null) {
                $retrievedAnimal = $this->createNewAnimal($declareArrayContent);
            }
        }

        return $retrievedAnimal;
    }

    /**

     * @return Ram|Ewe|Neuter
     */
    private function createNewAnimal($declareContentArray)
    {
        $declareContentArray = $declareContentArray->toArray();
        $declareType = $declareContentArray['type'];

        $gender = null;
        $animal = null;
        $animalContentArray = $declareContentArray['animal'];

        if(array_key_exists(Constant::GENDER_NAMESPACE, $animalContentArray)) {
            $gender = $animalContentArray[Constant::GENDER_NAMESPACE];
        }

        switch($gender) {
            case AnimalType::RAM:
                $animal = new Ram();
                break;
            case AnimalType::EWE:
                $animal = new Ewe();
                break;
            case AnimalType::NEUTER:
                $animal = new Neuter();
                break;
            default:
                $animal = new Neuter();
                break;
        }

        $ulnNumber = null;
        $countryCode = null;
        if (array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $animalContentArray) && array_key_exists(Constant::ULN_COUNTRY_CODE_NAMESPACE, $animalContentArray)) {
            $ulnNumber = $animalContentArray[Constant::ULN_NUMBER_NAMESPACE];
            $countryCode = $animalContentArray[Constant::ULN_COUNTRY_CODE_NAMESPACE];

        } else if(array_key_exists(Constant::PEDIGREE_NUMBER_NAMESPACE, $animalContentArray) && array_key_exists(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE, $animalContentArray)) {
            $ulnNumber = $animalContentArray[Constant::PEDIGREE_NUMBER_NAMESPACE];
            $countryCode = $animalContentArray[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE];
        }

        //Find registered tag, assign to this animal
        $tag = $this->entityManager->getRepository(Constant::TAG_REPOSITORY)->findByUlnNumberAndCountryCode($countryCode, $ulnNumber);
        $animal->setAssignedTag($tag);
        $animal->setAnimalType(AnimalType::sheep);
        $animal->setIsAlive(true);

        if(array_key_exists('date_of_birth', $declareContentArray)){
            $animal->setDateOfBirth(new \DateTime($declareContentArray['date_of_birth']));
        }

        if(array_key_exists('date_of_death', $declareContentArray)){
            $animal->setDateOfDeath(new \DateTime($declareContentArray['date_of_death']));
        }

        //Persist the new Neuter with an unregistered Tag
        $this->entityManager->persist($animal);
        //$this->entityManager->persist($tag);
        $this->entityManager->flush();

        return $animal;
    }

    //TODO Add switch case for setting values
//    <<<<<<< HEAD
//        if (array_key_exists('name', $animal)) {
//            $neuter->setName($animal['name']); }
//
//        //FIXME: if dateOfBirth is refactored out of Animal into the message, delete this
//        if (array_key_exists('date_of_birth', $animal)) {
//            $neuter->setDateOfBirth(new \DateTime($animal['date_of_birth'])); }
//
//        //FIXME: if dateOfDeath is refactored out of Animal into the message, delete this
//        if (array_key_exists('date_of_death', $animal)) {
//            $neuter->setDateOfDeath(new \DateTime($animal['date_of_death'])); }
//
//        if (array_key_exists('gender', $animal)) {
//            $neuter->setGender($animal['gender']); }
//
//        if (array_key_exists('parent_father', $animal)) {
//            $neuter->setParentFather($animal['parent_father']); }
//
//        if (array_key_exists('parent_mother', $animal)) {
//            $neuter->setParentMother($animal['parent_mother']); }
//
//        if (array_key_exists('parent_neuter', $animal)) {
//            $neuter->setParentNeuter($animal['parent_neuter']); }
//
//        if (array_key_exists('animal_type', $animal)) {
//            $neuter->setAnimalType($animal['animal_type']); }
//
//        if (array_key_exists('animal_category', $animal)) {
//            $neuter->setAnimalCategory($animal['animal_category']); }
//
//        if (array_key_exists('animal_working_number', $animal)) {
//            $neuter->setAnimalWorkingNumber($animal['animal_working_number']); }
//
//        if (array_key_exists('animal_hair_colour', $animal)) {
//            $neuter->setAnimalHairColour($animal['animal_hair_colour']); }
//
//        //Note a newly created neuter cannot have any messages yet,
//        //(like arrivals, departures, imports)
//        //so it is not necessary to map them here.
//        //TODO Check again for all messages if this is correct: A newly created neuter is also not created with any children.
//
//
//        $neuter->setAnimalType(AnimalType::sheep);
//=======

}