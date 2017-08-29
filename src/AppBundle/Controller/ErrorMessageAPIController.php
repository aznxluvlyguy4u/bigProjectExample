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
     *        "dataType"="boolean",
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
        return $this->get('app.declare.error_message')->getErrors($request);
    }


    /**
     *
     * Get ErrorMessage details of IR message by messageId
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
        return $this->get('app.declare.error_message')->getErrorDetails($request, $messageId);
    }


    /**
     *
     * Get ErrorMessage details of non-IR message by messageId
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
     * @Route("/non-ir/{messageId}")
     * @Method("GET")
     */
    public function getErrorDetailsNonIRmessage(Request $request, $messageId)
    {
        return $this->get('app.declare.error_message')->getErrorDetailsNonIRmessage($request, $messageId);
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
        return $this->get('app.declare.error_message')->updateError($request);
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
        return $this->get('app.declare.error_message')->updateNsfoDeclarationError($request, $messageId);
    }


    /**
     * Hide an error a user or admin does not want to see anymore.
     * At least one or both boolean values must be given: hide_for_admin or is_hidden.
     *
     * ### Request body ###
     *
     *  {
     *      "message_id": "4293920591adb11732d2",
     *      "is_ir_message": false,
     *      "hide_for_admin": false,
     *      "is_hidden": false
     *  }
     *
     *
     * ### Response body ###
     *
     *  {
     *      "result": {
     *          "message_id": "4293920591adb11732d2",
     *          "request_state": "FINISHED",
     *          "is_hidden": false,
     *          "hide_for_admin": false
     *      }
     *  }
     *
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
     *   description = "Hide an error a user or admin does not want to see anymore"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-hidden-status")
     * @Method("PUT")
     */
    public function updateHideStatus(Request $request)
    {
        return $this->get('app.declare.error_message')->updateHideStatus($request);
    }


    /**
     *
     * Get Dutch declare types
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
     *        "name"="formal",
     *        "dataType"="boolean",
     *        "required"=false,
     *        "description"=" choose if dutch output should be formal or informal, default = false",
     *        "format"="?formal=true"
     *      }
     *   },
     *   resource = true,
     *   description = "Get Dutch declare types"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-declare-types")
     * @Method("GET")
     */
    public function getDutchDeclareTypes(Request $request)
    {
        return $this->get('app.declare.error_message')->getDutchDeclareTypes($request);
    }
}