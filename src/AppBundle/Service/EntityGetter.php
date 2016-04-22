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

        $neuter->setAnimalType(AnimalType::sheep);

        //Persist the new neuter
        $this->entityManager->persist($neuter);
        $this->entityManager->flush();

        return $neuter;
    }

}