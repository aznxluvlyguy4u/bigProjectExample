<?php

namespace AppBundle\Controller;

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
      return $this->get('app.mate')->createMate($request);
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
   * @param $messageId
   * @return JsonResponse
   * @Route("/{messageId}")
   * @Method("PUT")
   */
  public function editMate(Request $request, $messageId)
  {
      return $this->get('app.mate')->editMate($request, $messageId);
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
      return $this->get('app.mate')->getMateHistory($request);
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
      return $this->get('app.mate')->getMateErrors($request);
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
      return $this->get('app.mate')->getMatingsToBeVerified($request);
  }

  
}