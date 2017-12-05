<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1/collars")
 */
class CollarAPIController extends APIController implements CollarAPIControllerInterface
{

  /**
   * Retrieve a list of Collar colour codes
   *
   * @ApiDoc(
   *   section = "Collars",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Retrieve a list of Collar colour codes"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  function getCollarCodes(Request $request)
  {
      return $this->get('app.collar')->getCollarCodes($request);
  }
}