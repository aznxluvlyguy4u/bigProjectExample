<?php

namespace AppBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
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
        $insertUlnManually = false;

        //Filter op pedigree of uln!
        if(array_key_exists('uln_number', $animal) && array_key_exists('uln_country_code', $animal)){

            $ulnNumber = $animal['uln_number'];
            $ulnCountryCode = $animal['uln_country_code'];

            $filterArray = array("ulnNumber" => $ulnNumber, "ulnCountryCode" => $ulnCountryCode);
            $retrievedAnimal = $this->entityManager->getRepository('AppBundle:Animal')->findOneBy($filterArray);

        } else if (array_key_exists('pedigree_number', $animal) && array_key_exists('pedigree_country_code', $animal)){

            $pedigreeNumber = $animal['pedigree_number'];
            $pedigreeCountryCode = $animal['pedigree_country_code'];

            $filterArray = array("pedigreeNumber" => $pedigreeNumber, "pedigreeCountryCode" => $pedigreeCountryCode);
            $retrievedAnimal = $this->entityManager->getRepository('AppBundle:Animal')->findOneBy($filterArray);

            $insertUlnManually = true;
        }

        //TODO if sheep doesn't exist persist it here
        //Onzijdig schaap aanmaken
        //Persisten
        //Onzijdig schaap als object teruggeven.

        return array("retrievedAnimal" => $retrievedAnimal,
                    "insertUlnManually" => $insertUlnManually);
    }

}