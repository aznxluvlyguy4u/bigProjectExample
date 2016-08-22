<?php

namespace AppBundle\Controller;

use AppBundle\Component\MateBuilder;
use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Client;
use AppBundle\Entity\Mate;
use AppBundle\Output\MateOutput;
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
    $this->persistAndFlush($mate);

    $output = MateOutput::createMateOverview($mate);
    
    return new JsonResponse([JsonInputConstant::RESULT => $output], 200);
  }
  
}