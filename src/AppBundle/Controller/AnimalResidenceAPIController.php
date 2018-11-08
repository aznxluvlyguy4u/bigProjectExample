<?php


namespace AppBundle\Controller;

use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Service\AnimalResidenceServiceInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;


/**
 * @Route("/api/v1/animal-residences")
 */
class AnimalResidenceAPIController extends APIController
{
    /**
     * @ApiDoc(
     *   section = "AnimalResidences",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the ADMIN that is registered with the API"
     *     },
     *     {
     *       "name"="animal",
     *       "dataType"="integer",
     *       "requirement"="\d+",
     *       "description"="The id of the animal for which the animalResidences will be retrieved"
     *     }
     *   },
     *   resource = true,
     *   description = "Retrieve a list of animalResidences for the selected animal"
     * )
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
     * @ApiDoc(
     *   section = "AnimalResidences",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the ADMIN that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Create multiple animalResidences and get all animalResidences of all included animals"
     * )
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
     * @ApiDoc(
     *   section = "AnimalResidences",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the ADMIN that is registered with the API"
     *     },
     *     {
     *       "name"="animalResidence",
     *       "dataType"="integer",
     *       "requirement"="\d+",
     *       "description"="The id of the animalResidence to be edited"
     *     }
     *   },
     *   resource = true,
     *   description = "Remove an animalResidence by id"
     * )
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
     * @ApiDoc(
     *   section = "AnimalResidences",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the ADMIN that is registered with the API"
     *     },
     *     {
     *       "name"="animalResidence",
     *       "dataType"="integer",
     *       "requirement"="\d+",
     *       "description"="The id of the animalResidence to be deleted"
     *     }
     *   },
     *   resource = true,
     *   description = "Delete an animalResidence by id"
     * )
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