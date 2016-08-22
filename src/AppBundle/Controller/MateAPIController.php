<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
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
   * @Route("")
   * @Method("POST")
   */
  public function postNewMate(Request $request)
  {
    $om = $this->getDoctrine()->getManager();

    $content = $this->getContentAsArray($request);
    $client = $this->getAuthenticatedUser($request);
    $location = $this->getSelectedLocation($request);
    $loggedInUser = $this->getLoggedInUser($request);

    $validateEweGender = true;
    $mateValidator = new MateValidator($om, $content, $client, $validateEweGender);
    if(!$mateValidator->getIsInputValid()) { return $mateValidator->createJsonResponse(); }

    dump('success!');die;
    
    return new JsonResponse("ok", 200);
  }
  
}