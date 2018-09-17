<?php

namespace AppBundle\Controller;

use AppBundle\Service\TagsService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/tags")
 */
class TagsAPIController extends APIController implements TagsAPIControllerInterface
{

  /**
   *
   * Retrieve a Tag by its ulnCountryCode and ulnNumber, concatenated, i.e.: NL123456789
   *
   * @ApiDoc(
   *   section = "Tags",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Retrieve a Tag by its ulnCountryCode and ulnNumber, concatenated."
   * )
   * @param Request $request the request object
   * @param $Id
   * @return JsonResponse
   * @Route("/{Id}")
   * @Method("GET")
   */
  public function getTagById(Request $request, $Id)
  {
      return $this->get(TagsService::class)->getTagById($request, $Id);
  }

  /**
   *
   * Retrieve either a list of all Tags, or a subset of Tags with a given state-type:
   * {
   *    ASSIGNED,
   *    UNASSIGNED,
   *    TRANSFERRED
   * }
   *
   * @ApiDoc(
   *   section = "Tags",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   parameters={
   *      {
   *        "name"="state",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"=" Tags to filter on",
   *        "format"="?state=state-type"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a list of Tags"
   * )
   * @param Request $request
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getTags(Request $request)
  {
      return $this->get(TagsService::class)->getTags($request);
  }


    /**
     *
     * Create a bunch of a Tags by plain text input of ULNs separated by separator symbol.
     *
     * @ApiDoc(
     *   section = "Tags",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="state",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"=" Tags to filter on",
     *        "format"="?state=state-type"
     *      }
     *   },
     *   resource = true,
     *   description = "Create a bunch of a Tags by plain text input of ULNs separated by separator symbol."
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("POST")
     */
    public function createTags(Request $request)
    {
        return $this->get(TagsService::class)->createTags($request);
    }
}