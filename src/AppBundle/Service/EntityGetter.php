<?php

namespace AppBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

class EntityGetter
{
    const AUTHORIZATION_HEADER_NAMESPACE = 'AccessToken';

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

        $ulnNumber = $animal['uln_number'];
        $ulnCountryCode = $animal['uln_country_code'];

        $pedigreeNumber = $animal['pedigree_number'];
        $pedigreeCountryCode = $animal['pedigree_country_code'];

        if ($ulnNumber != null && $ulnCountryCode != null){

            $filterArray = array("ulnNumber" => $ulnNumber, "ulnCountryCode" => $ulnCountryCode);
            $retrievedAnimal = $this->entityManager->getRepository('AppBundle:Animal')->findOneBy($filterArray);

        } else if ($pedigreeNumber != null && $pedigreeCountryCode != null){

            $filterArray = array("pedigreeNumber" => $pedigreeNumber, "pedigreeCountryCode" => $pedigreeCountryCode);
            $retrievedAnimal = $this->entityManager->getRepository('AppBundle:Animal')->findOneBy($filterArray);
        }

        return $retrievedAnimal;
    }

}