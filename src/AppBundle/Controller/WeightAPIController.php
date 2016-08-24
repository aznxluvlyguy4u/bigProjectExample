<?php

namespace AppBundle\Controller;

use AppBundle\Component\Modifier\DeclareWeightBuilder;
use AppBundle\Constant\Constant;
use AppBundle\Entity\DeclareWeight;
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

        
        //TODO VALIDATE IF MEASUREMENT ON THAT DATE ALREADY EXISTS
        $weightValidator = new DeclareWeightValidator($manager, $content, $client);
        if(!$weightValidator->getIsInputValid()) {
            return $weightValidator->createJsonResponse();
        }

        $declareWeight = DeclareWeightBuilder::post($manager, $content, $client, $loggedInUser, $location);
        $manager->persist($declareWeight->getWeightMeasurement());
        $this->persistAndFlush($declareWeight);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $declareWeight->getWeightMeasurement()), 200);
    }
}