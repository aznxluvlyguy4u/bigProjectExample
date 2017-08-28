<?php

namespace AppBundle\Controller;

use AppBundle\Component\ArrivalMessageBuilder;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareDepartResponse;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareExportResponse;
use AppBundle\Entity\Location;
use AppBundle\Entity\Message;
use AppBundle\Enumerator\AnimalTransferStatus;
use AppBundle\Enumerator\MessageType;
use AppBundle\Enumerator\RecoveryIndicatorType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/departs")
 */
class DepartAPIController extends APIController implements DepartAPIControllerInterface {

  /**
   * Get a DeclareDepart, found by it's ID.
   *
   * @ApiDoc(
   *   section = "Departs",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Retrieve a DeclareDepart by given ID"
   * )
   * @param Request $request the request object
   * @param int $Id Id of the DeclareDepart to be returned
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareDepartRepository")
   * @Method("GET")
   */
  public function getDepartById(Request $request, $Id)
  {
      return $this->get('app.depart')->getDepartById($request, $Id);
  }


  /**
   * Retrieve either a list of all DeclareDepartures or a subset of DeclareDepartures with a given state-type:
   * {
   *    OPEN,
   *    FINISHED,
   *    FAILED,
   *    CANCELLED
   * }
   *
   * @ApiDoc(
   *   section = "Departs",
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
   *        "description"=" DeclareDepartures to filter on",
   *        "format"="?state=state-type"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a a list of DeclareDepartures"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getDepartures(Request $request)
  {
      return $this->get('app.depart')->getDepartures($request);
  }


    /**
    *
    * Create a new DeclareDepart Request.
    *
    * @ApiDoc(
    *   section = "Departs",
    *   requirements={
    *     {
    *       "name"="AccessToken",
    *       "dataType"="string",
    *       "requirement"="",
    *       "description"="A valid accesstoken belonging to the user that is registered with the API"
    *     }
    *   },
    *   resource = true,
    *   description = "Post a DeclareDepart request"
    * )
    * @param Request $request the request object
    * @return JsonResponse
    * @Route("")
    * @Method("POST")
    */
    public function createDepart(Request $request)
    {
        return $this->get('app.depart')->createDepart($request);
    }


  /**
   *
   * Update existing DeclareDepart Request.
   *
   * @ApiDoc(
   *   section = "Departs",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Update a DeclareDepart request"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareDepartRepository")
   * @Method("PUT")
   */
  public function updateDepart(Request $request, $Id)
  {
      return $this->get('app.depart')->updateDepart($request, $Id);
  }

  /**
   *
   * Get DeclareDeparts & DeclareExports which have failed last responses.
   *
   * @ApiDoc(
   *   section = "Departs",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get DeclareDeparts & DeclareExports which have failed last responses"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-errors")
   * @Method("GET")
   */
  public function getDepartErrors(Request $request)
  {
      return $this->get('app.depart')->getDepartErrors($request);
  }


  /**
   *
   * For the history view, get DeclareDeparts & DeclareExports which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED
   *
   * @ApiDoc(
   *   section = "Departs",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get DeclareDeparts & DeclareExports which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-history")
   * @Method("GET")
   */
  public function getDepartHistory(Request $request)
  {
      return $this->get('app.depart')->getDepartHistory($request);
  }
}