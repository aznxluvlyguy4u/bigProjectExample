<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Output\ClientOverviewOutput;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\Collection;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/clients")
 */
class ClientAPIController extends APIController {

  /**
   * Retrieve either a list of all Clients or a Client belonging to a certain UBN:
   *
   * @ApiDoc(
   *   section = "Clients",
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
   *        "name"="ubn",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"=" Client to filter on",
   *        "format"="?ubn=ubn-type"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve either a list of all Clients or a Client belonging to a certain UBN"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getClients(Request $request)
  {
      return $this->getClientService()->getClients($request);
  }

  /**
   *
   * Create a Client
   *
   * @Route("")
   * @Method("POST")
   */
  public function createClient(Request $request)
  {
      return $this->getClientService()->createClient($request);
  }

  /**
   *
   * Debug endpoint
   *
   * @Route("/debug")
   * @Method("GET")
   */
  public function debugAPI(Request $request) {
    return new JsonResponse("ok", 200);
  }
}