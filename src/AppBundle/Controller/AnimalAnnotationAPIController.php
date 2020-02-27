<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\AnimalAnnotationService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;


/**
 * @Route("/api/v1/animals")
 */
class AnimalAnnotationAPIController extends APIController implements AnimalAnnotationAPIControllerInterface {

    /**
     * Get all annotations for animal by ULN or id. For example NL100029511721 or 12345.
     *
     * @ApiDoc(
     *   section = "Animal Annotations",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get all annotations for animal by ULN or id. For example NL100029511721 or 12345"
     * )
     * @param Request $request the request object
     * @param string $idOrUlnString
     * @return JsonResponse
     * @Route("/{idOrUlnString}/annotations")
     * @Method("GET")
     * @throws \Exception
     */
    public function getAnnotations(Request $request, $idOrUlnString)
    {
        return $this->get(AnimalAnnotationService::class)->getAnnotations($request, $idOrUlnString);
    }


    /**
     * Create or update user related animal annotation for animal by ULN or id. For example NL100029511721 or 12345.
     *
     * @ApiDoc(
     *   section = "Animal Annotations",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Create or update user related animal annotation for animal by ULN or id. For example NL100029511721 or 12345"
     * )
     * @param Request $request the request object
     * @param string $idOrUlnString
     * @return JsonResponse
     * @Route("/{idOrUlnString}/annotations")
     * @Method("PUT")
     * @throws \Exception
     */
    public function editAnnotation(Request $request, $idOrUlnString)
    {
        return $this->get(AnimalAnnotationService::class)->editAnnotation($request, $idOrUlnString);
    }
}
