<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\LossService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/v1/losses")
 */
class LossAPIController extends APIController implements LossAPIControllerInterface {

  /**
   * Get a DeclareLoss, found by it's ID.
   *
   * @ApiDoc(
   *   section = "Losses",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Retrieve a DeclareLoss by given ID"
   * )
   * @param Request $request the request object
   * @param int $Id Id of the DeclareLoss to be returned
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareLossRepository")
   * @Method("GET")
   */
  public function getLossById(Request $request, $Id)
  {
      return $this->get(LossService::class)->getLossById($request, $Id);
  }


  /**
   * Retrieve either a list of all DeclareLosses or a subset of DeclareLosses with a given state-type:
   * {
   *    OPEN,
   *    FINISHED,
   *    FAILED,
   *    CANCELLED
   * }
   *
   * @ApiDoc(
   *   section = "Losses",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   parameters={
   *      {
   *        "name"="state",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"=" DeclareLosses to filter on",
   *        "format"="?state=state-type"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a a list of DeclareLosses"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getLosses(Request $request)
  {
      return $this->get(LossService::class)->getLosses($request);
  }


  /**
   *
   * Create a new DeclareLoss Request.
   *
   * @ApiDoc(
   *   section = "Losses",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Post a DeclareLoss request"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
  public function createLoss(Request $request)
  {
      return $this->get(LossService::class)->createLoss($request);
  }


    /**
     *
     * Resend open DeclareLoss Requests.
     *
     * @ApiDoc(
     *   section = "Losses",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to a developer that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Resend open DeclareLoss Requests"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/resend")
     * @Method("POST")
     */
    public function resendCreateLoss(Request $request)
    {
        return $this->get(LossService::class)->resendCreateLoss($request);
    }


  /**
   *
   * Update existing DeclareLoss Request.
   *
   * @ApiDoc(
   *   section = "Losses",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Update a DeclareLoss request"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareLossRepository")
   * @Method("PUT")
   */
  public function editLoss(Request $request, $Id)
  {
      return $this->get(LossService::class)->editLoss($request, $Id);
  }


  /**
   *
   * Get DeclareLosses which have failed last responses.
   *
   * @ApiDoc(
   *   section = "Losses",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get DeclareLosses which have failed last responses"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-errors")
   * @Method("GET")
   */
  public function getLossErrors(Request $request)
  {
      return $this->get(LossService::class)->getLossErrors($request);
  }


  /**
   *
   * For the history view, get DeclareLosses which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED
   *
   * @ApiDoc(
   *   section = "Losses",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get DeclareLosses which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-history")
   * @Method("GET")
   */
  public function getLossHistory(Request $request)
  {
      return $this->get(LossService::class)->getLossHistory($request);
  }
}