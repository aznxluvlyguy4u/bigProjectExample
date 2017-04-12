<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Output\MenuBarOutput;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
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
        if($client == null) { return Validator::createJsonResponse('Client cannot be null', 428); }
        $outputArray = MenuBarOutput::create($client);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
    }

    /**
    * @param Request $request
    * @return JsonResponse
    * @Route("/admin-menu-bar")
    * @Method("GET")
    */
    public function getAdminMenuBar(Request $request) {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) {return $validationResult->getJsonResponse();}

        $outputArray = MenuBarOutput::createAdmin($this->getUser());

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
    }
}