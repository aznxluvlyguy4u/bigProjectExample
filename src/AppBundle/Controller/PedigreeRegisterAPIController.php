<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/v1/pedigreeregisters")
 */
class PedigreeRegisterAPIController extends APIController implements PedigreeRegisterAPIControllerInterface {

  /**
   * Get PedigreeRegisters.
   *
   * @ApiDoc(
   *   section = "Pedigree Register",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get PedigreeRegisters",
   *   output = "AppBundle\Entity\PedigreeRegister"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getPedigreeRegisters(Request $request)
  {
      return $this->get('app.pedigree_register')->getPedigreeRegisters($request);
  }


}