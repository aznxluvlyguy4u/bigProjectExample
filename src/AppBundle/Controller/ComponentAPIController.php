<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Output\MenuBarOutput;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1/components")
 */
class ComponentAPIController extends APIController {

  /**
   * Get data for menu bar at the top.
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
   *   description = "Get data for menu bar at the top.",
   *   output = "AppBundle\Entity\Client"
   * )
   * @param Request $request
   * @return JsonResponse
   * @Route("/menu-bar")
   * @Method("GET")
   */
  public function getMenuBar(Request $request) {
    $client = $this->getAuthenticatedUser($request);
    $outputArray = MenuBarOutput::create($client);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
  }

}