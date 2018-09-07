<?php


namespace AppBundle\Controller;

use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Service\AnimalResidenceServiceInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;


/**
 * @Route("/api/v1/animal-residences")
 */
class AnimalResidenceAPIController extends APIController
{
    /**
     * @Method("GET")
     * @Route("/animal/{animal}")
     * @param Request $request
     * @param Animal $animal
     * @return array
     */
    function getResidencesByAnimal(Request $request, Animal $animal)
    {
        return $this->get(AnimalResidenceServiceInterface::class)->getResidencesByAnimal($request, $animal);
    }

    /**
     * @Method("POST")
     * @Route("")
     * @param Request $request
     * @return array
     */
    function createResidence(Request $request)
    {
        return $this->get(AnimalResidenceServiceInterface::class)->createResidence($request);
    }

    /**
     * @Method("PUT")
     * @Route("/{animalResidence}")
     * @param Request $request
     * @param AnimalResidence $animalResidence
     * @return array
     */
    function editResidence(Request $request, AnimalResidence $animalResidence)
    {
        return $this->get(AnimalResidenceServiceInterface::class)->editResidence($request, $animalResidence);
    }

    /**
     * @Method("DELETE")
     * @Route("/{animalResidence}")
     * @param Request $request
     * @param AnimalResidence $animalResidence
     * @return array
     */
    function deleteResidence(Request $request, AnimalResidence $animalResidence)
    {
        return $this->get(AnimalResidenceServiceInterface::class)->deleteResidence($request, $animalResidence);
    }

}