<?php


namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Class VwaEmployeeAPIController
 * @package AppBundle\Controller
 *
 * @Route("/api/v1/vwa-employee")
 */
class VwaEmployeeAPIController extends APIController implements VwaEmployeeAPIControllerInterface
{

    /**
     * Get overview of all VWA Employees.
     *
     * ### Response body ###
     *
     *  {
     *      "result": [
     *          {
     *              "person_id": "da1494820dfc7052c90e67f2d34f9a27950b8cd5",
     *              "first_name": "Johnny",
     *              "last_name": "Boyz",
     *              "email_address": "jb@vwa.nl",
     *              "is_active": true,
     *              "invitation_date": "2017-09-05T12:14:17+0200",
     *              "type": "VwaEmployee"
     *          },
     *          {
     *              "person_id": "da1494820dfc7052c90e67f2d34f9a27950b8cd6",
     *              "first_name": "John",
     *              "last_name": "Man",
     *              "email_address": "jbm@vwa.nl",
     *              "is_active": true,
     *              "invitation_date": "2017-09-05T12:14:17+0200",
     *              "type": "VwaEmployee"
     *          }
     *      ]
     *  }
     *
     * @ApiDoc(
     *   section = "VWA",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   statusCodes={
     *     200="Returned when successful",
     *     401="Unauthorized"
     *   },
     *   description = "Get overview of all VWA Employees."
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("GET")
     */
    function getAll(Request $request)
    {
        return $this->get('app.vwa.employee')->getAll($request);
    }


    /**
     * Get details of a single VWA Employee. A VWA Employee can use "me" instead of "{id}" to get their own data.
     *
     * ### Response body ###
     *
     * {
     *  "result":
     *      {
     *          "person_id": "da1494820dfc7052c90e67f2d34f9a27950b8cd5",
     *          "first_name": "Dadio",
     *          "last_name": "Masters",
     *          "email_address": "dm@vwa.nl",
     *          "is_active": true,
     *          "invitation_date": "2017-09-05T12:14:17+0200",
     *          "invited_by":
     *          {
     *              "person_id": "1e71b4c3d52365fc21ea2a48d997728712c3ecc3",
     *              "first_name": "Admin",
     *              "last_name": "Wanita",
     *              "email_address": "aw@nsfo.nl",
     *              "is_active": true,
     *              "type": "Employee"
     *          },
     *          "type": "VwaEmployee"
     *      }
     * }
     *
     * @ApiDoc(
     *   section = "VWA",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   statusCodes={
     *     200="Returned when successful",
     *     400="Bad request if no vwa employee has found for the given id",
     *     401="Unauthorized"
     *   },
     *   description = "Get details of a single VWA Employee."
     * )
     * @param string $id
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/{id}")
     * @Method("GET")
     */
    function getById(Request $request, $id)
    {
        return $this->get('app.vwa.employee')->getById($request, $id);
    }


    /**
     * Create a new VWA Employee. An invitation email with the login data is send after creation.
     *
     * ### Request body ###
     *
     *  {
     *      "first_name":"Dadio",
     *      "last_name":"Masters",
     *      "email_address":"dm@vwa.nl"
     *  }
     *
     * ### Response body ###
     *
     * {
     *  "result":
     *      {
     *          "person_id": "da1494820dfc7052c90e67f2d34f9a27950b8cd5",
     *          "first_name": "Dadio",
     *          "last_name": "Masters",
     *          "email_address": "dm@vwa.nl",
     *          "is_active": true,
     *          "invitation_date": "2017-09-05T12:14:17+0200",
     *          "invited_by":
     *          {
     *              "person_id": "1e71b4c3d52365fc21ea2a48d997728712c3ecc3",
     *              "first_name": "Admin",
     *              "last_name": "Wanita",
     *              "email_address": "aw@nsfo.nl",
     *              "is_active": true,
     *              "type": "Employee"
     *          },
     *          "type": "VwaEmployee"
     *      }
     * }
     *
     * @ApiDoc(
     *   section = "VWA",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   statusCodes={
     *     200="Returned when successful",
     *     400="Bad request if an active vwa employee already exists, or input format is incorrect",
     *     401="Unauthorized"
     *   },
     *   description = "Create a new VWA Employee. An invitation email with the login data is send after creation. "
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("POST")
     */
    function create(Request $request)
    {
        return $this->get('app.vwa.employee')->create($request);
    }


    /**
     * Edit details of a single VWA Employee.
     *
     * To skip editing certain values this endpoint provides two options.
     * Patch: You can exclude they key-value pairs you do not want to edit.
     * Put: you can return them with the values unchanged.
     *
     * Note! passwords may only be changed by the VWA employee themselves.
     *
     * ### Request body ###
     *
     *  {
     *      "first_name":"Dadio",
     *      "last_name":"Masters",
     *      "email_address":"dm@vwa.nl",
     *      "password": "askjfdkfigjjdkjfkj"
     *  }
     *
     * ### Response body ###
     *
     * {
     *  "result":
     *      {
     *          "person_id": "da1494820dfc7052c90e67f2d34f9a27950b8cd5",
     *          "first_name": "Dadio",
     *          "last_name": "Masters",
     *          "email_address": "dm@vwa.nl",
     *          "is_active": true,
     *          "invitation_date": "2017-09-05T12:14:17+0200",
     *          "invited_by":
     *          {
     *              "person_id": "1e71b4c3d52365fc21ea2a48d997728712c3ecc3",
     *              "first_name": "Admin",
     *              "last_name": "Wanita",
     *              "email_address": "aw@nsfo.nl",
     *              "is_active": true,
     *              "type": "Employee"
     *          },
     *          "type": "VwaEmployee"
     *      }
     * }
     *
     * @ApiDoc(
     *   section = "VWA",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   statusCodes={
     *     200="Returned when successful",
     *     400="Bad request if vwa employee has already been deactivated, or input format is incorrect",
     *     401="Unauthorized, including when an password change is requested by someone other than the own user"
     *   },
     *   description = "Edit details of a single VWA Employee."
     * )
     * @param string $id
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/{id}")
     * @Method("PUT")
     */
    function edit(Request $request, $id)
    {
        return $this->get('app.vwa.employee')->edit($request, $id);
    }


    /**
     * Deactivate a single VWA Employee.
     *
     * ### Response body ###
     *
     * {
     *  "result":
     *      {
     *          "person_id": "da1494820dfc7052c90e67f2d34f9a27950b8cd5",
     *          "first_name": "Dadio",
     *          "last_name": "Masters",
     *          "email_address": "dm@vwa.nl",
     *          "is_active": true,
     *          "invitation_date": "2017-09-05T12:14:17+0200",
     *          "invited_by":
     *          {
     *              "person_id": "1e71b4c3d52365fc21ea2a48d997728712c3ecc3",
     *              "first_name": "Admin",
     *              "last_name": "Wanita",
     *              "email_address": "aw@nsfo.nl",
     *              "is_active": true,
     *              "type": "Employee"
     *          },
     *          "type": "VwaEmployee"
     *      }
     * }
     *
     * @ApiDoc(
     *   section = "VWA",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   statusCodes={
     *     200="Returned when successful",
     *     400="Bad request if vwa employee has already been deactivated",
     *     401="Unauthorized"
     *   },
     *   description = "Deactivate a single VWA Employee."
     * )
     * @param string $id
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/{id}")
     * @Method("DELETE")
     */
    function deactivate(Request $request, $id)
    {
        return $this->get('app.vwa.employee')->deactivate($request, $id);
    }


    /**
     * Retrieve a valid access token.
     *
     * ### Response body ###
     *
     *  {
     *      "result": {
     *          "access_token": "i8e278fnhe__some_access_token__fWGWQGqgt42twh",
     *          "user": {
     *              "first_name": "Charlie",
     *              "last_name": "Checker",
     *              "email_address": "cc@vwa.nl"
     *          }
     *      }
     *  }
     *
     * @ApiDoc(
     *   section = "VWA",
     *   requirements={
     *     {
     *       "name"="Authorization header",
     *       "dataType"="string",
     *       "requirement"="Base64 encoded",
     *       "format"="Authorization: Basic xxxxxxx==",
     *       "description"="Basic Authentication, Base64 encoded string with delimiter"
     *     }
     *   },
     *   resource = true,
     *   statusCodes={200="Returned when successful",401="Unauthorized"},
     *   description = "Retrieve a valid access token."
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/auth/authorize")
     * @Method("GET")
     */
    function authorize(Request $request)
    {
        return $this->get('app.vwa.employee')->authorize($request);
    }


}