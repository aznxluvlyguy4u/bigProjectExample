<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BreedTypeAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/breed-type")
 */
class BreedTypeAPIController extends APIController implements BreedTypeAPIControllerInterface
{
    /**
     * Get BreedTypes.
     *
     * @ApiDoc(
     *   section = "Breed Type",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get BreedTypes",
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("GET")
     */
    function getBreedTypes(Request $request)
    {
        return $this->get('app.breed_type')->getBreedTypes($request);
    }

}