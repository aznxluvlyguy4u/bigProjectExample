<?php


namespace AppBundle\Service;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalResidence;
use Symfony\Component\HttpFoundation\Request;

interface AnimalResidenceServiceInterface
{
    function getResidencesByAnimal(Request $request, Animal $animal);
    function createResidence(Request $request);
    function editResidence(Request $request, AnimalResidence $animalResidence);
    function deleteResidence(Request $request, AnimalResidence $animalResidence);
}