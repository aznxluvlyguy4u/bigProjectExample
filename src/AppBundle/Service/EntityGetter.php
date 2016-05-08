<?php

namespace AppBundle\Service;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Tag;
use AppBundle\Enumerator\AnimalType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Proxies\__CG__\AppBundle\Entity\Ewe;
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
        $retrievedAnimal = $declareArrayContent->get(Constant::ANIMAL_NAMESPACE);

        $animalRepository = $this->entityManager->getRepository(Constant::ANIMAL_REPOSITORY);

        //At least a uln or pedigree number + country code combination must be given to find an Animal
        if(array_key_exists(Constant::ULN_NAMESPACE, $retrievedAnimal) && array_key_exists(Constant::ULN_COUNTRY_CODE_NAMESPACE, $retrievedAnimal)){
            $ulnNumber = $retrievedAnimal[Constant::ULN_NAMESPACE];
            $ulnCountryCode = $retrievedAnimal[Constant::ULN_COUNTRY_CODE_NAMESPACE];
            $retrievedAnimal = $animalRepository->findByCountryCodeAndUlnOrPedigree($ulnCountryCode, $ulnNumber);

            if($retrievedAnimal == null) {
                $retrievedAnimal = $this->createNewAnimal($declareArrayContent);
            }

        } else if (array_key_exists(Constant::PEDIGREE_NAMESPACE, $retrievedAnimal) && array_key_exists(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE, $retrievedAnimal)){
            $pedigreeNumber = $retrievedAnimal[Constant::PEDIGREE_NAMESPACE];
            $pedigreeCountryCode = $retrievedAnimal[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE];
            $retrievedAnimal = $animalRepository->findByCountryCodeAndUlnOrPedigree($pedigreeCountryCode, $pedigreeNumber);

            if($retrievedAnimal == null) {
                $retrievedAnimal = $this->createNewAnimal($declareArrayContent);
            }
        }

        return $retrievedAnimal;
    }

    /**

     * @return Neuter
     */
    private function createNewAnimal($declareContentArray)
    {
        $declareContentArray = $declareContentArray->toArray();

        $gender = null;
        $animal = null;
        $animalContentArray = $declareContentArray[Constant::ANIMAL_NAMESPACE];

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
        if (array_key_exists(Constant::ULN_NAMESPACE, $animalContentArray) && array_key_exists(Constant::ULN_COUNTRY_CODE_NAMESPACE, $animalContentArray)) {
            $ulnNumber = $animalContentArray[Constant::ULN_NAMESPACE];
            $countryCode = $animalContentArray[Constant::ULN_COUNTRY_CODE_NAMESPACE];

        } else if(array_key_exists(Constant::PEDIGREE_NAMESPACE, $animalContentArray) && array_key_exists(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE, $animalContentArray)) {
            $ulnNumber = $animalContentArray[Constant::PEDIGREE_NAMESPACE];
            $countryCode = $animalContentArray[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE];
        }

        //Find registered tag, assign to this animal
        $tag = $this->entityManager->getRepository(Constant::TAG_REPOSITORY)->findByUlnNumberAndCountryCode($countryCode, $ulnNumber);
        $animal->setAssignedTag($tag);
        $animal->setAnimalType(AnimalType::sheep);
        $animal->setIsAlive(true);

        if(array_key_exists(Constant::DATE_OF_BIRTH_NAMESPACE, $declareContentArray)){
            $animal->setDateOfBirth(new \DateTime($declareContentArray[Constant::DATE_OF_BIRTH_NAMESPACE]));
        }

        if(array_key_exists(Constant::DATE_OF_DEATH_NAMESPACE, $declareContentArray)){
            $animal->setDateOfDeath(new \DateTime($declareContentArray[Constant::DATE_OF_DEATH_NAMESPACE]));
        }

        //Persist the new Neuter with an unregistered Tag
        $this->entityManager->persist($animal);
        //$this->entityManager->persist($tag);
        $this->entityManager->flush();

        return $animal;
    }

}