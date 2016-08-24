<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Employee;
use AppBundle\FormInput\WeightMeasurements;
use AppBundle\Output\WeightMeasurementsOutput;
use AppBundle\Validation\DeclareWeightValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\DataMapper\RadioListMapper;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/animals-weights")
 */
class WeightAPIController extends APIController
{
    /**
     * Get the last weight measurements of all the animals in a clients livestock.
     *
     * @ApiDoc(
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get the last weight measurements of all the animals in a clients livestock",
     *   output = "AppBundle\Entity\Animal"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("GET")
     */
    public function getLastWeightMeasurements(Request $request)
    {
        $client = $client = $this->getAuthenticatedUser($request);
        $location = $this->getSelectedLocation($request);
        $animals = $this->getDoctrine()
            ->getRepository(Constant::ANIMAL_REPOSITORY)->getLiveStock($location);

        $minimizedOutput = WeightMeasurementsOutput::createForAnimals($animals);

        return new JsonResponse(array (Constant::RESULT_NAMESPACE => $minimizedOutput), 200);
    }

    /**
     *
     * Create new weight measurements for the given animals.
     *
     * @ApiDoc(
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Create new weight measurements for the given animals",
     *   input = "AppBundle\Entity\Animals",
     *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
     * )
     *
     * @param Request $request the request object
     * @return jsonResponse
     * @Route("")
     * @Method("POST")
     */
    public function createWeightMeasurements(Request $request)
    {
        $manager = $this->getDoctrine()->getManager();
        $content = $this->getContentAsArray($request);
        $client = $this->getAuthenticatedUser($request);
        $location = $this->getSelectedLocation($request);
        $loggedInUser = $this->getLoggedInUser($request);

        //Validate password format
        $weightValidator = new DeclareWeightValidator($manager, $content, $client);
        if(!$weightValidator->getIsInputValid()) {
            return $weightValidator->createJsonResponse();
        }

        dump('Success');die;

        $location = $this->getSelectedLocation($request);
        $livestockAnimals = $this->getDoctrine()
            ->getRepository(Constant::ANIMAL_REPOSITORY)->getLiveStock($location);

        //Persist updated changes and return the updated values
        $manager = $this->getDoctrine()->getManager();
        $objects = WeightMeasurements::createAndPersist($content, $livestockAnimals, $manager);
        $updatedAnimals = $objects[Constant::ANIMALS_NAMESPACE];

        //TODO verify with frontend: Return output for all animals, or only animals with new weight measurements.
        //TODO Perhaps the boolean below could also be set in het jsonInput. Or maybe just return an "OK" string.
        $outputOnlyUpdatedAnimals = true;

        if($outputOnlyUpdatedAnimals) {
            $minimizedOutput = WeightMeasurementsOutput::createForAnimals($updatedAnimals);

        } else {  //Output for all animals
            $minimizedOutput = WeightMeasurementsOutput::createForAnimals($livestockAnimals);
        }

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $minimizedOutput), 200);
    }
}