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
     * Get details of a single VWA Employee.
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