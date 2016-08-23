<?php

namespace AppBundle\Controller;

use AppBundle\Component\MateBuilder;
use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Client;
use AppBundle\Entity\Mate;
use AppBundle\Entity\MateRepository;
use AppBundle\Enumerator\RequestType;
use AppBundle\Output\MateOutput;
use AppBundle\Output\Output;
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
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Create a DeclareMate Request",
   *   input = "AppBundle\Entity\Mate",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
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

    $validateEweGender = true;
    $mateValidator = new MateValidator($manager, $content, $client, $validateEweGender);
    if(!$mateValidator->getIsInputValid()) { return $mateValidator->createJsonResponse(); }

    $mate = MateBuilder::post($manager, $content, $client, $loggedInUser, $location);

    //TODO when messaging system is complete, have the studRam owner confirm the mate
    MateBuilder::approveMateDeclaration($mate, $loggedInUser);

    $this->persistAndFlush($mate);

    $output = MateOutput::createMateOverview($mate);
    
    return new JsonResponse([JsonInputConstant::RESULT => $output], 200);
  }



  
  
  /**
   *
   * For the history view, get Mates which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED
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
   *   description = "Get Matings which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED",
   *   input = "AppBundle\Entity\Mate",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
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
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get Mates that were rejected by the third party",
   *   input = "AppBundle\Entity\Mate",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
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
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get Mates that still need to be verified by the third party",
   *   input = "AppBundle\Entity\Mate",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
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


  /**
   *
   * Revoke Mate
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
   *   description = "Revoke Mate",
   *   input = "AppBundle\Entity\Mate",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/{messageId}/revoke")
   * @Method("PUT")
   */
  public function revokeMate(Request $request, $messageId)
  {
    $manager = $this->getDoctrine()->getManager();
    $client = $this->getAuthenticatedUser($request);
    $loggedInUser = $this->getLoggedInUser($request);

    $mateFromMessageId = MateValidator::isNonRevokedMateOfClient($manager, $client, $messageId);
    if(!($mateFromMessageId instanceof Mate)) {
      return Output::createStandardJsonErrorResponse();
    }
    
    $mate = MateBuilder::revoke($mateFromMessageId, $loggedInUser);
    $this->persistAndFlush($mate);

    $output = MateOutput::createMateOverview($mate);

    return new JsonResponse([JsonInputConstant::RESULT => $output], 200);
  }
}