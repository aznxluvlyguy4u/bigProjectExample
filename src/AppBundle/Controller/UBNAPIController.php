<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\UbnService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/v1/ubns")
 */
class UBNAPIController extends APIController implements UBNAPIControllerInterface
{

  /**
   * Create a RetrieveUbnDetails request
   *
   * @ApiDoc(
   *   section = "UBNs",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Post a RetrieveUbnDetails request"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
  function getUBNDetails(Request $request)
  {
      return $this->get(UbnService::class)->getUBNDetails($request);
  }


  /**
   *
   * Get list of UBN Processors.
   *
   * @ApiDoc(
   *   section = "UBNs",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get list of UBN Processors"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/processors")
   * @Method("GET")
   */
  public function getUbnProcessors(Request $request)
  {
      return $this->get(UbnService::class)->getUbnProcessors($request);
  }


    /**
     *
     * Get list of all active UBNs.
     *
     * @ApiDoc(
     *   section = "UBNs",
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
     *        "name"="active_only",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="set to false to return more data, it is true by default",
     *        "format"="?active_only=false"
     *      }
     *   },
     *   resource = true,
     *   description = "Get list of all active UBNs"
     * )
     *
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("GET")
     */
    public function getAll(Request $request)
    {
        return $this->get(UbnService::class)->getAll($request);
    }

}