<?php

namespace AppBundle\Service;

use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestType;
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

            //Find registered tag, assign to this animal
            $tag = $this->entityManager->getRepository(Constant::TAG_REPOSITORY)->findByUlnNumberAndCountryCode($countryCode, $ulnNumber);
            $animal->setAssignedTag($tag);
            $animal->setAnimalType(AnimalType::sheep);
            $animal->setIsAlive(true);

            //Set the uln in the newly created animal
            $animal->setUlnCountryCode($countryCode);
            $animal->setUlnNumber($ulnNumber);

        } else if(array_key_exists(Constant::PEDIGREE_NUMBER_NAMESPACE, $animalContentArray) && array_key_exists(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE, $animalContentArray)) {
            $pedigreeNumber = $animalContentArray[Constant::PEDIGREE_NUMBER_NAMESPACE];
            $countryCode = $animalContentArray[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE];

            //Set the pedigree number in the newly created animal
            $animal->setPedigreeCountryCode($countryCode);
            $animal->setPedigreeNumber($pedigreeNumber);
        }

        //Transfer values from the array to the newly created animal
        if (array_key_exists('name', $declareContentArray)) {
            $animal->setName($declareContentArray['name']); }

        if (array_key_exists('date_of_birth', $declareContentArray)) {
            $animal->setDateOfBirth(new \DateTime($declareContentArray['date_of_birth'])); }

        if (array_key_exists('date_of_death', $declareContentArray)) {
            $animal->setDateOfDeath(new \DateTime($declareContentArray['date_of_death'])); }

        if (array_key_exists('gender', $declareContentArray)) {
            $animal->setGender($declareContentArray['gender']); }

        if (array_key_exists('parent_father', $declareContentArray)) {
            $animal->setParentFather($declareContentArray['parent_father']); }

        if (array_key_exists('parent_mother', $declareContentArray)) {
            $animal->setParentMother($declareContentArray['parent_mother']); }

        if (array_key_exists('parent_neuter', $declareContentArray)) {
            $animal->setParentNeuter($declareContentArray['parent_neuter']); }

        if (array_key_exists('animal_type', $declareContentArray)) {
            $animal->setAnimalType($declareContentArray['animal_type']);
        } else { //by default it is a sheep
            $animal->setAnimalType(AnimalType::sheep);
        }

        if (array_key_exists('animal_category', $declareContentArray)) {
            $animal->setAnimalCategory($declareContentArray['animal_category']); }

        if (array_key_exists('animal_order_number', $declareContentArray)) {
            $animal->setAnimalOrderNumber($declareContentArray['animal_order_number']); }

        if (array_key_exists('animal_hair_colour', $declareContentArray)) {
            $animal->setAnimalHairColour($declareContentArray['animal_hair_colour']); }

        //Note a newly created animal cannot have any messages yet,
        //(like arrivals, departures, imports)
        //so it is not necessary to map them here.
        //TODO Check if it is necessary to also create an new animal including the children/parents/surrogateMother etc.


        //TODO After the june2016 deadline organize the if statements above in this switch case
        switch($declareType) {
            case RequestType::DECLARATION_DETAIL_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
            case RequestType::DECLARE_ANIMAL_FLAG_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
            case RequestType::DECLARE_ARRIVAL_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
            case RequestType::DECLARE_BIRTH_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
            case RequestType::DECLARE_DEPART_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
            case RequestType::DECLARE_EARTAGS_TRANSFER_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
            case RequestType::DECLARE_LOSS_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
            case RequestType::DECLARE_EXPORT_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
            case RequestType::DECLARE_IMPORT_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
            case RequestType::RETRIEVE_EARTAGS_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
            case RequestType::REVOKE_DECLARATION_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
            default:
                break;

        }


        //Persist the new Ram/Ewe/Neuter with an unregistered Tag
        $this->entityManager->persist($animal);
        //$this->entityManager->persist($tag);
        $this->entityManager->flush();

        return $animal;
    }
}