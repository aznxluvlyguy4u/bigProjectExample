<?php

namespace AppBundle\Controller;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/errors")
 */
class ErrorMessageAPIController extends APIController implements ErrorMessageAPIControllerInterface
{

    /**
     *
     * Get ErrorMessages
     *
     * @ApiDoc(
     *   section = "Errors",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="show_hidden",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="show error messages hidden for admin, default = false",
     *        "format"="?show_hidden=true"
     *      }
     *   },
     *   resource = true,
     *   description = "Get ErrorMessages"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("GET")
     */
    public function getErrors(Request $request)
    {
        return $this->getErrorMessageService()->getErrors($request);
    }


    /**
     *
     * Get ErrorMessage details by id
     *
     * @ApiDoc(
     *   section = "Errors",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get ErrorMessage details by id"
     * )
     * @param Request $request the request object
     * @param String $messageId
     * @return JsonResponse
     * @Route("/{messageId}")
     * @Method("GET")
     */
    public function getErrorDetails(Request $request, $messageId)
    {
        return $this->getErrorMessageService()->getErrorDetails($request, $messageId);
    }


    /**
     * Hide an error a user does not want to see anymore,
     * by updating the existing DeclareBase's isRemovedByUser to true.
     *
     * @ApiDoc(
     *   section = "Errors",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "hide an error response for any IR-declaration"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("PUT")
     */
    public function updateError(Request $request)
    {
        return $this->getErrorMessageService()->updateError($request);
    }


    /**
     * Hide an error a user does not want to see anymore,
     * by updating the existing DeclareNsfoBase's isRemovedByUser to true.
     *
     * @ApiDoc(
     *   section = "Errors",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "hide an error response for any non-IR-declaration"
     * )
     * @param Request $request the request object
     * @param String $messageId
     * @return JsonResponse
     * @Route("-nsfo/{messageId}")
     * @Method("PUT")
     */
    public function updateNsfoDeclarationError(Request $request, $messageId)
    {
        return $this->getErrorMessageService()->updateNsfoDeclarationError($request, $messageId);
    }
}