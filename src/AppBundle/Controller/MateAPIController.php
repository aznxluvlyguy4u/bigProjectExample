<?php

namespace AppBundle\Controller;

use AppBundle\Component\MateBuilder;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Client;
use AppBundle\Entity\Mate;
use AppBundle\Entity\MateRepository;
use AppBundle\Output\MateOutput;
use AppBundle\Output\Output;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Validation\MateValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/matings")
 */
class MateAPIController extends APIController {

  /**
   *
   * Create a DeclareMate Request.
   *
   * @ApiDoc(
   *   section = "Matings",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Create a DeclareMate Request"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
  public function createMate(Request $request)
  {
    $manager = $this->getDoctrine()->getManager();

    $content = $this->getContentAsArray($request);
    $client = $this->getAuthenticatedUser($request);
    $location = $this->getSelectedLocation($request);
    $loggedInUser = $this->getLoggedInUser($request);

    $log = ActionLogWriter::createMate($manager, $client, $loggedInUser, $location, $content);

    $validateEweGender = true;
    $mateValidator = new MateValidator($manager, $content, $client, $validateEweGender);
    if(!$mateValidator->getIsInputValid()) { return $mateValidator->createJsonResponse(); }

    $mate = MateBuilder::post($manager, $content, $client, $loggedInUser, $location);

    //TODO when messaging system is complete, have the studRam owner confirm the mate
    MateBuilder::approveMateDeclaration($mate, $loggedInUser);

    $this->persistAndFlush($mate);

    $output = MateOutput::createMateOverview($mate);

    $log = ActionLogWriter::completeActionLog($manager, $log);
    
    return new JsonResponse([JsonInputConstant::RESULT => $output], 200);
  }
  
  
  /**
   *
   * Edit Mate
   *
   * @ApiDoc(
   *   section = "Matings",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Edit Mate"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/{messageId}")
   * @Method("PUT")
   */
  public function editMate(Request $request, $messageId)
  {
    $manager = $this->getDoctrine()->getManager();
    $client = $this->getAuthenticatedUser($request);
    $loggedInUser = $this->getLoggedInUser($request);
    $content = $this->getContentAsArray($request);
    $content->set(JsonInputConstant::MESSAGE_ID, $messageId);
    $location = $this->getSelectedLocation($request);

    $log = ActionLogWriter::editMate($manager, $client, $loggedInUser, $location, $content);
    
    $validateEweGender = true;
    $isPost = false;
    $mateValidator = new MateValidator($manager, $content, $client, $validateEweGender, $isPost);
    if(!$mateValidator->getIsInputValid()) { return $mateValidator->createJsonResponse(); }

    $mate = $mateValidator->getMateFromMessageId();
    $mate = MateBuilder::edit($manager, $mate, $content, $client, $loggedInUser, $location);

    //TODO when messaging system is complete, have the studRam owner confirm the mate
    MateBuilder::approveMateDeclaration($mate, $loggedInUser);

    $this->persistAndFlush($mate);

    $output = MateOutput::createMateOverview($mate);

    $log = ActionLogWriter::completeActionLog($manager, $log);
    
    return new JsonResponse([JsonInputConstant::RESULT => $output], 200);
  }
  
  
  /**
   *
   * For the history view, get Mates which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED
   *
   * @ApiDoc(
   *   section = "Matings",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get Matings which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-history")
   * @Method("GET")
   */
  public function getMateHistory(Request $request)
  {
    $location = $this->getSelectedLocation($request);

    /** @var MateRepository $repository */
    $repository = $this->getDoctrine()->getRepository(Mate::class);
    $matings = $repository->getMatingsHistoryOutput($location);

    return new JsonResponse([JsonInputConstant::RESULT => $matings],200);
  }


  /**
   *
   * Get Mates that were rejected by the third party.
   *
   * @ApiDoc(
   *   section = "Matings",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get Mates that were rejected by the third party"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-errors")
   * @Method("GET")
   */
  public function getMateErrors(Request $request)
  {
    $location = $this->getSelectedLocation($request);

    /** @var MateRepository $repository */
    $repository = $this->getDoctrine()->getRepository(Mate::class);
    $matings = $repository->getMatingsErrorOutput($location);

    return new JsonResponse([JsonInputConstant::RESULT => $matings],200);
  }


  /**
   *
   * Get Mates that still need to be verified by the third party.
   *
   * @ApiDoc(
   *   section = "Matings",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get Mates that still need to be verified by the third party"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-pending")
   * @Method("GET")
   */
  public function getMatingsToBeVerified(Request $request)
  {
    $location = $this->getSelectedLocation($request);

    /** @var MateRepository $repository */
    $repository = $this->getDoctrine()->getRepository(Mate::class);
    $matings = $repository->getMatingsStudRamOutput($location);

    return new JsonResponse([JsonInputConstant::RESULT => $matings],200);
  }

  
}