<?php

namespace AppBundle\Controller;

use AppBundle\Service\TagReplaceService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Class TagsReplaceAPI
 * @Route("/api/v1/tags-replace")
 */
class TagsReplaceAPIController extends APIController {

  /**
   *
   * Create a new DeclareTagReplace request
   *
   * @ApiDoc(
   *   section = "Tag Replace",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Post a new DeclareTagReplace request, containing a Tag to be replaced"
   * )
   * @param Request $request
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
  public function createTagReplaceRequest(Request $request)
  {
      return $this->get(TagReplaceService::class)->createTagReplaceRequest($request);
  }


    /**
     *
     * Resend open DeclareTagReplace requests
     *
     * @ApiDoc(
     *   section = "Tag Replace",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Post a new DeclareTagReplace request, containing a Tag to be replaced"
     * )
     * @param Request $request
     * @return JsonResponse
     * @Route("-resend")
     * @Method("POST")
     */
    public function resendOpenTagReplaceRequest(Request $request)
    {
        return $this->get(TagReplaceService::class)->resendOpenTagReplaceRequest($request);
    }


    /**
    * @param Request $request
    * @return JsonResponse
    * @Route("-history")
    * @Method("GET")
    */
    public function getTagReplaceHistory(Request $request)
    {
        return $this->get(TagReplaceService::class)->getTagReplaceHistory($request);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("-errors")
     * @Method("GET")
     */
    public function getTagReplaceErrors(Request $request)
    {
        return $this->get(TagReplaceService::class)->getTagReplaceErrors($request);
    }
}