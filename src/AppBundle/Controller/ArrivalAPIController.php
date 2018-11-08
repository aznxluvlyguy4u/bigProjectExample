<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse as JsonResponse;
use AppBundle\Service\ArrivalService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/v1/arrivals")
 */
class ArrivalAPIController extends APIController implements ArrivalAPIControllerInterface
{

  /**
   * Retrieve a DeclareArrival, found by it's ID.
   *
   * @ApiDoc(
   *   section = "Arrivals",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Retrieve a DeclareArrival by given ID"
   * )
   * @param Request $request the request object
   * @param int $Id Id of the DeclareArrival to be returned
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareArrivalRepository")
   * @Method("GET")
   */
  public function getArrivalById(Request $request, $Id)
  {
      return $this->get(ArrivalService::class)->getArrivalById($request, $Id);
  }

  /**
   * Retrieve either a list of all DeclareArrivals or a subset of DeclareArrivals with a given state-type:
   * {
   *    OPEN,
   *    FINISHED,
   *    FAILED,
   *    CANCELLED,
   *    REVOKING,
   *    REVOKED
   * }
   *
   * @ApiDoc(
   *   section = "Arrivals",
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
   *        "description"=" DeclareArrivals to filter on",
   *        "format"="?state=state-type"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a a list of DeclareArrivals"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getArrivals(Request $request)
  {
      return $this->get(ArrivalService::class)->getArrivals($request);
  }


  /**
   * Create a new DeclareArrival or DeclareImport request
   *
   * @ApiDoc(
   *   section = "Arrivals",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Post a DeclareArrival or DeclareImport request"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
    public function createArrivalOrImport(Request $request)
    {
        return $this->get(ArrivalService::class)->createArrivalOrImport($request);
    }

  /**
   * Update existing DeclareArrival or DeclareImport request
   *
   * @ApiDoc(
   *   section = "Arrivals",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Update a DeclareArrival or DeclareImport request"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareArrivalRepository")
   * @Method("PUT")
   */
  public function updateArrival(Request $request, $Id)
  {
      return $this->get(ArrivalService::class)->updateArrival($request, $Id);
  }


  /**
   *
   * Get DeclareArrivals & DeclareImports which have failed last responses.
   *
   * @ApiDoc(
   *   section = "Arrivals",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get DeclareArrivals & DeclareImports which have failed last responses"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-errors")
   * @Method("GET")
   */
  public function getArrivalErrors(Request $request)
  {
      return $this->get(ArrivalService::class)->getArrivalErrors($request);
  }


  /**
   *
   * For the history view, get DeclareArrivals & DeclareImports which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED.
   *
   * @ApiDoc(
   *   section = "Arrivals",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get DeclareArrivals & DeclareImports which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-history")
   * @Method("GET")
   */
  public function getArrivalHistory(Request $request)
  {
      return $this->get(ArrivalService::class)->getArrivalHistory($request);
  }


}