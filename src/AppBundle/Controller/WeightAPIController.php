<?php

namespace AppBundle\Controller;

use AppBundle\Component\DeclareWeightBuilder;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\DeclareWeight;
use AppBundle\Entity\DeclareWeightRepository;
use AppBundle\Entity\Employee;
use AppBundle\FormInput\WeightMeasurements;
use AppBundle\Output\WeightMeasurementsOutput;
use AppBundle\Util\ActionLogWriter;
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

        $log = ActionLogWriter::createDeclareWeight($manager, $client, $loggedInUser, $content);

        $weightValidator = new DeclareWeightValidator($manager, $content, $client);
        if(!$weightValidator->getIsInputValid()) {
            return $weightValidator->createJsonResponse();
        }

        $declareWeight = DeclareWeightBuilder::post($manager, $content, $client, $loggedInUser, $location);
        $manager->persist($declareWeight->getWeightMeasurement());
        $this->persistAndFlush($declareWeight);

        $log = ActionLogWriter::completeActionLog($manager, $log); 

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'OK'), 200);
    }


    /**
     *
     * Edit DeclareWeight and WeightMeasurements
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
     *   description = "Edit Mate",
     *   input = "AppBundle\Entity\Mate",
     *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
     * )
     *
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/{messageId}")
     * @Method("PUT")
     */
    public function editWeightMeasurements(Request $request, $messageId)
    {
        $manager = $this->getDoctrine()->getManager();
        $client = $this->getAuthenticatedUser($request);
        $loggedInUser = $this->getLoggedInUser($request);
        $content = $this->getContentAsArray($request);
        $content->set(JsonInputConstant::MESSAGE_ID, $messageId);
        $location = $this->getSelectedLocation($request);

        $log = ActionLogWriter::editDeclareWeight($manager, $client, $loggedInUser, $content);

        $isPost = false;
        $weightValidator = new DeclareWeightValidator($manager, $content, $client, $isPost);
        if(!$weightValidator->getIsInputValid()) {
            return $weightValidator->createJsonResponse();
        }

        $declareWeight = $weightValidator->getDeclareWeightFromMessageId();
        $declareWeight = DeclareWeightBuilder::edit($manager, $declareWeight, $content, $client, $loggedInUser, $location);

        $this->persistAndFlush($declareWeight);

        $log = ActionLogWriter::completeActionLog($manager, $log);

        return new JsonResponse([JsonInputConstant::RESULT => 'OK'], 200);
    }


    /**
     *
     * For the history view, get DeclareWeights which have the following requestState: FINISHED or REVOKED
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
     *   description = "Get DeclareWeights which have the following requestState: FINISHED or REVOKED",
     *   input = "AppBundle\Entity\DeclareWeight",
     *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
     * )
     *
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-history")
     * @Method("GET")
     */
    public function getDeclareWeightHistory(Request $request)
    {
        $location = $this->getSelectedLocation($request);

        /** @var DeclareWeightRepository $repository */
        $repository = $this->getDoctrine()->getRepository(DeclareWeight::class);
        $declareWeights = $repository->getDeclareWeightsHistoryOutput($location);

        return new JsonResponse([JsonInputConstant::RESULT => $declareWeights],200);
    }
}