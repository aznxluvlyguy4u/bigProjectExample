<?php


namespace AppBundle\Service;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;

class AnimalResidenceService extends ControllerServiceBase implements AnimalResidenceServiceInterface
{
    /**
     * @param Request $request
     * @param Animal $animal
     * @return array
     */
    function getResidencesByAnimal(Request $request, Animal $animal)
    {
        if(!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN)) {
            throw AdminValidator::standardException();
        }

        return $this->getBaseSerializer()->getDecodedJson(
            $animal->getAnimalResidenceHistory(),
            [JmsGroup::EDIT_OVERVIEW, JmsGroup::BASIC_SUB_ANIMAL_DETAILS],
            true
        );
    }

    function createResidence(Request $request)
    {
        if(!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN)) {
            throw AdminValidator::standardException();
        }

         $request->getContent();

        // TODO: Implement createResidence() method.
    }

    function editResidence(Request $request, AnimalResidence $animalResidence)
    {
        if(!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN)) {
            throw AdminValidator::standardException();
        }

        // TODO: Implement editResidence() method.
    }

    function deleteResidence(Request $request, AnimalResidence $animalResidence)
    {
        if(!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN)) {
            throw AdminValidator::standardException();
        }

        // TODO: Implement deleteResidence() method.
    }

}