<?php

namespace AppBundle\Service;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Tag;
use AppBundle\Enumerator\AnimalType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\CssSelector\Node\NegationNode;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\DateTime;

class EntityGetter
{
    const ULN_NAMESPACE = "uln_number";
    const PEDIGREE_NAMESPACE = "pedigree_number";
    const ULN_COUNTRY_CODE_NAMESPACE = "uln_country_code";
    const PEDIGREE_COUNTRY_CODE_NAMESPACE = "pedigree_country_code";

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
     * @param $animal
     * @return Neuter|array|null
     */
    public function retrieveAnimal($animal)
    {
        //By default just return the original animal
        $retrievedAnimal = $animal;

        $animalRepository = $this->entityManager->getRepository(Constant::ANIMAL_REPOSITORY);

        //At least a uln or pedigree number + country code combination must be given to find an Animal
        if(array_key_exists($this::ULN_NAMESPACE, $animal) && array_key_exists($this::ULN_COUNTRY_CODE_NAMESPACE, $animal)){
            $ulnNumber = $animal[$this::ULN_NAMESPACE];
            $ulnCountryCode = $animal[$this::ULN_COUNTRY_CODE_NAMESPACE];
            $retrievedAnimal = $animalRepository->findByCountryCodeAndUlnOrPedigree($ulnCountryCode, $ulnNumber);

            if($retrievedAnimal == null) {
                $retrievedAnimal = $this->createANewNeuter($animal);
            }

        } else if (array_key_exists($this::PEDIGREE_NAMESPACE, $animal) && array_key_exists($this::PEDIGREE_COUNTRY_CODE_NAMESPACE, $animal)){
            $pedigreeNumber = $animal[$this::PEDIGREE_NAMESPACE];
            $pedigreeCountryCode = $animal[$this::PEDIGREE_COUNTRY_CODE_NAMESPACE];
            $retrievedAnimal = $animalRepository->findByCountryCodeAndUlnOrPedigree($pedigreeCountryCode, $pedigreeNumber);

            if($retrievedAnimal == null) {
                $retrievedAnimal = $this->createANewNeuter($animal);
            }
        }

        return $retrievedAnimal;
    }

    /**
     * @param $animal
     * @return Neuter
     */
    private function createANewNeuter($animal)
    {
        //Create an unregistered tag
        $tag = new Tag();
        $tag->setIsVerified(false);
        $tag->setUlnNumber(Constant::UNKNOWN_NAMESPACE);
        $tag->setUlnCountryCode(Constant::UNKNOWN_NAMESPACE);
        $tag->setAnimalOrderNumber(Constant::UNKNOWN_NAMESPACE);
        $tag->setOrderDate(new \DateTime());

        $neuter = new Neuter();
        $tag->setAnimal($neuter);
        $neuter->setIsAlive(true);

        if (array_key_exists($this::ULN_NAMESPACE, $animal)) {
            $tag->setUlnNumber($animal[$this::ULN_NAMESPACE]);
        }

        if (array_key_exists($this::ULN_COUNTRY_CODE_NAMESPACE, $animal)) {
            $tag->setUlnCountryCode($animal[$this::ULN_COUNTRY_CODE_NAMESPACE]);
        }

        if (array_key_exists($this::PEDIGREE_NAMESPACE, $animal)) {
            $neuter->setPedigreeNumber($animal[$this::PEDIGREE_NAMESPACE]);
        }

        if (array_key_exists($this::PEDIGREE_COUNTRY_CODE_NAMESPACE, $animal)) {
            $neuter->setPedigreeCountryCode($animal[$this::PEDIGREE_COUNTRY_CODE_NAMESPACE]);
        }

        $neuter->setAnimalType(AnimalType::sheep);

        //Persist the new Neuter with an unregistered Tag
        $this->entityManager->persist($neuter);
        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        return $neuter;
    }

}