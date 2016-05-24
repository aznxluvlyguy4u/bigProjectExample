<?php

namespace AppBundle\Service;

use Exception;
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
     * @return null|Tag
     */
    public function retrieveTag($ulnCountryCode, $ulnNumber)
    {

        $tagRepository = $this->entityManager->getRepository(Constant::TAG_REPOSITORY);
        return $tagRepository->findByUlnNumberAndCountryCode($ulnCountryCode, $ulnNumber);
    }

    /**
     * @param ArrayCollection $declareArrayContent
     * @param string $animalKey
     * @return Neuter|array|null
     */
    public function retrieveAnimal($declareArrayContent)
    {
        $retrievedAnimal = null;
        $retrievedAnimalArray = $declareArrayContent->get(Constant::ANIMAL_NAMESPACE);

        $ulnNumberExists = array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $retrievedAnimalArray);
        $pedigreeNumberExists = array_key_exists(Constant::PEDIGREE_NUMBER_NAMESPACE, $retrievedAnimalArray);

        if($ulnNumberExists){
            $retrievedAnimal = $this->retrieveAnimalFromUln($retrievedAnimalArray);

            if($retrievedAnimal == null) { $retrievedAnimal = $this->createNewAnimal($declareArrayContent); }

        } else if ($pedigreeNumberExists){
            $retrievedAnimal = $this->retrieveAnimalFromPedigree($retrievedAnimalArray);
            if($retrievedAnimal == null) { $retrievedAnimal = $this->createNewAnimal($declareArrayContent); }
        }

        return $retrievedAnimal;
    }

    /**
     * @param array $animalArray
     * @return Ewe|Neuter|Ram|null
     */
    private function createAnimalBasedOnGender(array $animalArray)
    {
        $gender = null;
        $animal = null;

        if(array_key_exists(Constant::GENDER_NAMESPACE, $animalArray)) {
            $gender = $animalArray[Constant::GENDER_NAMESPACE];
        }

        switch($gender) {
            case AnimalType::MALE:
                $animal = new Ram();
                break;
            case AnimalType::FEMALE:
                $animal = new Ewe();
                break;
            default:
                $animal = new Neuter();
                break;
        }
        return $animal;
    }

    /**
     * @param ArrayCollection $declareContentArray
     * @return Ram|Ewe|Neuter
     */
    private function createNewAnimal($declareContentArray)
    {
        $declareContentArray = $declareContentArray->toArray();
        $animalArray = $declareContentArray[Constant::ANIMAL_NAMESPACE];

        $animal = $this->createAnimalBasedOnGender($animalArray);

        $ulnNumber = null;
        $countryCode = null;
        if (array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $animalArray) && array_key_exists(Constant::ULN_COUNTRY_CODE_NAMESPACE, $animalArray)) {
            $ulnNumber = $animalArray[Constant::ULN_NUMBER_NAMESPACE];
            $countryCode = $animalArray[Constant::ULN_COUNTRY_CODE_NAMESPACE];

            //Find registered tag, assign to this animal
            $tag = $this->entityManager->getRepository(Constant::TAG_REPOSITORY)->findByUlnNumberAndCountryCode($countryCode, $ulnNumber);
            $animal->setAssignedTag($tag);
            $animal->setAnimalType(AnimalType::sheep);
            $animal->setIsAlive(true);

            //Set the uln in the newly created animal
            $animal->setUlnCountryCode($countryCode);
            $animal->setUlnNumber($ulnNumber);

        } else if(array_key_exists(Constant::PEDIGREE_NUMBER_NAMESPACE, $animalArray) && array_key_exists(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE, $animalArray)) {
            $pedigreeNumber = $animalArray[Constant::PEDIGREE_NUMBER_NAMESPACE];
            $countryCode = $animalArray[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE];

            //Set the pedigree number in the newly created animal
            $animal->setPedigreeCountryCode($countryCode);
            $animal->setPedigreeNumber($pedigreeNumber);
        }

        $animal = $this->mapArrayValuesToAnimal($animal, $animalArray, $declareContentArray);

        return $animal;
    }

    /**
     * @param Ram|Ewe|Neuter $animalObject
     * @param array $declareContentArray
     * @param array $animalArray
     * @return Ram|Ewe|Neuter
     */
    public function mapArrayValuesToAnimal($animalObject, $animalArray, $declareContentArray)
    {
        $declareType = $declareContentArray['type'];

//        //Transfer values from the array to the newly created animal
//        if (array_key_exists('animal_type', $animalArray) && $animalArray['animal_type'] != null) {
//            $animalObject->setAnimalType($animalArray['animal_type']);
//        } else { //by default it is a sheep
//            $animalObject->setAnimalType(AnimalType::sheep);
//        }

        //Note a newly created animal cannot have any messages yet,
        //(like arrivals, departures, imports)
        //so it is not necessary to map them here.

        switch($declareType) {
            case RequestType::DECLARATION_DETAIL_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
                break;

            case RequestType::DECLARE_ANIMAL_FLAG_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
                break;

            case RequestType::DECLARE_ARRIVAL_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
                break;

            case RequestType::DECLARE_BIRTH_ENTITY:
                if (array_key_exists('date_of_birth', $declareContentArray)) {
                    $animalObject->setDateOfBirth(new \DateTime($declareContentArray['date_of_birth'])); }

                if (array_key_exists('father', $declareContentArray)) {
                    $declareContentArray['father'][Constant::GENDER_NAMESPACE] = AnimalType::MALE;
                    $father = $this->retrieveAnimalFromAnimalArray($declareContentArray['father']);
                    $animalObject->setParentFather($father);
                }

                if (array_key_exists('mother', $declareContentArray)) {
                    $declareContentArray['mother'][Constant::GENDER_NAMESPACE] = AnimalType::FEMALE;
                    $mother = $this->retrieveAnimalFromAnimalArray( $declareContentArray['mother']);
                    $animalObject->setParentMother($mother); }

                if (array_key_exists('surrogate', $animalArray)) {
                    $animalArray['surrogate'][Constant::GENDER_NAMESPACE] = AnimalType::FEMALE;
                    $surrogate = $this->retrieveAnimalFromAnimalArray($animalArray['surrogate']);
                    $animalObject->setSurrogate($surrogate); }

                break;

            case RequestType::DECLARE_DEPART_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
                break;

            case RequestType::DECLARE_TAGS_TRANSFER_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
                break;

            case RequestType::DECLARE_LOSS_ENTITY:
                if (array_key_exists('date_of_death', $declareContentArray)) {
                    $animalObject->setDateOfDeath(new \DateTime($declareContentArray['date_of_death'])); }

                break;

            case RequestType::DECLARE_EXPORT_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
                break;

            case RequestType::DECLARE_IMPORT_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
                break;
            case RequestType::RETRIEVE_TAGS_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
                break;

            case RequestType::REVOKE_DECLARATION_ENTITY:
                //TODO: only add the mininum required fields for this Message Type
                break;

            default:
                //Mappings not used at the moment
                if (array_key_exists('name', $declareContentArray)) {
                    $animalObject->setName($declareContentArray['name']); }

                if (array_key_exists('animal_category', $declareContentArray)) {
                    $animalObject->setAnimalCategory($declareContentArray['animal_category']); }

                if (array_key_exists('animal_order_number', $declareContentArray)) {
                    $animalObject->setAnimalOrderNumber($declareContentArray['animal_order_number']); }

                if (array_key_exists('animal_hair_colour', $declareContentArray)) {
                    $animalObject->setAnimalHairColour($declareContentArray['animal_hair_colour']); }

                break;
        }
        return $animalObject;
    }

    /**
     * This function is for parents and surrogates inside a declare that are not labeled 'animal'.
     *
     * @param array $animalArray
     * @return Neuter|array|null
     */
    private function retrieveAnimalFromAnimalArray($animalArray)
    {
        $retrievedAnimal = null;
        $animalRepository = $this->entityManager->getRepository(Constant::ANIMAL_REPOSITORY);

        $ulnExists =  array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $animalArray);
        $pedigreeCodeExists = array_key_exists(Constant::PEDIGREE_NUMBER_NAMESPACE, $animalArray);

        if($ulnExists) {
            $retrievedAnimal = $this->retrieveAnimalFromUln($animalArray);

            if($retrievedAnimal == null) {
                $retrievedAnimal = $this->createNewAnimalFromAnimalArray($animalArray);
            }
        } else if ($pedigreeCodeExists) {
            $retrievedAnimal = $this->retrieveAnimalFromPedigree($animalArray);

            if ($retrievedAnimal == null) {
                $retrievedAnimal = $this->createNewAnimalFromAnimalArray($animalArray);
            }
        }
        return $retrievedAnimal;
    }

    private function retrieveAnimalFromUln(array $animalArray)
    {
        $animal = null;
        $animalRepository = $this->entityManager->getRepository(Constant::ANIMAL_REPOSITORY);

        $ulnNumberExists = array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $animalArray);
        $ulnCountryCodeExists = array_key_exists(Constant::ULN_COUNTRY_CODE_NAMESPACE, $animalArray);

        if ($ulnNumberExists && $ulnCountryCodeExists) {
            $ulnNumber = $animalArray[Constant::ULN_NUMBER_NAMESPACE];
            $countryCode = $animalArray[Constant::ULN_COUNTRY_CODE_NAMESPACE];

            $animal = $animalRepository->findByUlnOrPedigreeCountryCodeAndNumber($countryCode, $ulnNumber);
        }

        return $animal;
    }

    private function retrieveAnimalFromPedigree(array $animalArray)
    {
        $animal = null;
        $animalRepository = $this->entityManager->getRepository(Constant::ANIMAL_REPOSITORY);

        $pedigreeNumberExists = array_key_exists(Constant::PEDIGREE_NUMBER_NAMESPACE, $animalArray);
        $pedigreeCountryCodeExists = array_key_exists(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE, $animalArray);

        if ($pedigreeNumberExists && $pedigreeCountryCodeExists) {
            $pedigreeNumber = $animalArray[Constant::PEDIGREE_NUMBER_NAMESPACE];
            $pedigreeCountryCode = $animalArray[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE];

            $animal = $animalRepository->findByPedigreeCountryCodeAndNumber($pedigreeCountryCode, $pedigreeNumber);

            return $animal;
        }
    }


    /**
     * @param array $animalArray
     * @return Ram|Ewe|Neuter
     * @throws Exception
     */
    private function createNewAnimalFromAnimalArray(array $animalArray)
    {
        $animal = $this->createAnimalBasedOnGender($animalArray);

        $ulnNumber = null;
        $countryCode = null;
        $tagRepository = $this->entityManager->getRepository(Constant::TAG_REPOSITORY);
        $locationRepository = $this->entityManager->getRepository(Constant::LOCATION_REPOSITORY);

        $ulnNumberExists = array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $animalArray);
        $ulnCountryCodeExists = array_key_exists(Constant::ULN_COUNTRY_CODE_NAMESPACE, $animalArray);
        $pedigreeNumberExists = array_key_exists(Constant::PEDIGREE_NUMBER_NAMESPACE, $animalArray);
        $pedigreeCountryCodeExists = array_key_exists(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE, $animalArray);

        if ($ulnNumberExists && $ulnCountryCodeExists) {
            $ulnNumber = $animalArray[Constant::ULN_NUMBER_NAMESPACE];
            $countryCode = $animalArray[Constant::ULN_COUNTRY_CODE_NAMESPACE];

            //Find registered tag, assign to this animal
            $tag = $tagRepository->findByUlnNumberAndCountryCode($countryCode, $ulnNumber);

            if($tag->getTagStatus() == "assigned") { throw new Exception('Tag has already been assigned'); }

            $animal->setAssignedTag($tag);
            $animal->setAnimalType(AnimalType::sheep);
            $animal->setIsAlive(true);

            //Set the uln in the newly created animal
            $animal->setUlnCountryCode($countryCode);
            $animal->setUlnNumber($ulnNumber);

        } else if($pedigreeNumberExists && $pedigreeCountryCodeExists) {
            $pedigreeNumber = $animalArray[Constant::PEDIGREE_NUMBER_NAMESPACE];
            $countryCode = $animalArray[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE];

            //Set the pedigree number in the newly created animal
            $animal->setPedigreeCountryCode($countryCode);
            $animal->setPedigreeNumber($pedigreeNumber);

            $animal->setAnimalType(AnimalType::sheep);
            $animal->setIsAlive(true);
        }


        //Map all the other possible values
        if (array_key_exists('name', $animalArray)) {
            $animal->setName($animalArray['name']); }

        if (array_key_exists('date_of_birth', $animalArray)) {
            $animal->setDateOfBirth(new \DateTime($animalArray['date_of_birth'])); }

        if (array_key_exists('date_of_death', $animalArray)) {
            $animal->setDateOfDeath(new \DateTime($animalArray['date_of_death'])); }

//FIXME Setting an father, mother or surrogate here as well, might lead to a large loop
//        if (array_key_exists('father', $animalArray)) {
//            $animalArray['father'][Constant::GENDER_NAMESPACE] = AnimalType::MALE;
//            $father = $this->retrieveAnimalFromAnimalArray($animalArray['father']);
//            $animal->setParentFather($father);
//        }
//
//        if (array_key_exists('mother', $animalArray)) {
//            $animalArray['mother'][Constant::GENDER_NAMESPACE] = AnimalType::FEMALE;
//            $mother = $this->retrieveAnimalFromAnimalArray( $animalArray['mother']);
//            $animal->setParentMother($mother); }
//
//        if (array_key_exists('surrogate', $animalArray)) {
//            $animalArray['surrogate'][Constant::GENDER_NAMESPACE] = AnimalType::FEMALE;
//            $surrogate = $this->retrieveAnimalFromAnimalArray($animalArray['surrogate']);
//            $animal->setSurrogate($surrogate); }

        if (array_key_exists('animal_category', $animalArray)) {
            $animal->setAnimalCategory($animalArray['animal_category']); }

        if (array_key_exists('animal_hair_colour', $animalArray)) {
            $animal->setAnimalHairColour($animalArray['animal_hair_colour']); }

        if (array_key_exists('birth_tail_length', $animalArray)) {
            $animal->setBirthTailLength($animalArray['birth_tail_length']); }

        if (array_key_exists('location', $animalArray)) {
            $location = $locationRepository->findByLocationArray($animalArray['location']);
            $animal->setLocation($location); }

        return $animal;
    }

    /**
     * @param $messageId
     * @return \AppBundle\Entity\DeclareBaseResponse
     */
    public function getResponseDeclarationByMessageId($messageId)
    {
        $response = $this->entityManager->getRepository(Constant::DECLARE_BASE_RESPONSE_REPOSITORY)->findOneBy(array('messageId'=>$messageId));

        return $response;
    }

    /**
     * @param string $messageNumber
     * @return \AppBundle\Entity\DeclareBase
     */
    public function getRequestMessageByMessageNumber($messageNumber)
    {
        $requestId = $this->getResponseMessageByMessageNumber($messageNumber)->getRequestId();

        return $this->entityManager->getRepository(Constant::DECLARE_BASE_REPOSITORY)->findOneBy(array(Constant::REQUEST_ID_NAMESPACE=>$requestId));
    }

    /**
     * @param $messageNumber
     * @return \AppBundle\Entity\DeclareBaseResponse
     */
    public function getResponseMessageByMessageNumber($messageNumber)
    {
        return $this->entityManager->getRepository(Constant::DECLARE_BASE_RESPONSE_REPOSITORY)->findOneBy(array(Constant::MESSAGE_NUMBER_CAMEL_CASE_NAMESPACE=>$messageNumber));
    }
}