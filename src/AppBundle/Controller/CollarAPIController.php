<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
   * @return Response
   * @Route("")
   * @Method("GET")
   */
  function getCollarCodes(Request $request) {

    $collarColourCodes = $this->getDoctrine()
      ->getRepository(Constant::COLLAR_REPOSITORY)
      ->findAll();

    return new JsonResponse(array(Constant::RESULT_NAMESPACE=>$collarColourCodes), 200);
  }
}