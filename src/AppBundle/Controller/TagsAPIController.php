<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Tag;
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
   *      },
   *     {
   *        "name"="ignore_location",
   *        "dataType"="boolean",
   *        "required"=false,
   *        "description"=" Choose whether to return only tags linked to selected location or all tags linked to client. By default is false for NL locations and true for non-NL locations",
   *        "format"="?ignore_location=false"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a list of Tags"
   * )
   * @param Request $request
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   * @throws \Exception
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
     *      },
     *     {
     *        "name"="ignore_location",
     *        "dataType"="boolean",
     *        "required"=false,
     *        "description"=" Choose whether to return only tags linked to selected location or all tags linked to client. By default is false for NL locations and true for non-NL locations",
     *        "format"="?ignore_location=false"
     *      }
     *   },
     *   resource = true,
     *   description = "Create a bunch of a Tags by plain text input of ULNs separated by separator symbol."
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("POST")
     * @throws \Exception
     */
    public function createTags(Request $request)
    {
        return $this->get(TagsService::class)->createTags($request);
    }


    /**
     * @ApiDoc(
     *   section = "Tags",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the ADMIN that is registered with the API"
     *     },
     *     {
     *       "name"="tag",
     *       "dataType"="integer",
     *       "requirement"="\d+",
     *       "description"="The id of the tag to be deleted"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="state",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"=" Tags to filter on",
     *        "format"="?state=state-type"
     *      },
     *     {
     *        "name"="ignore_location",
     *        "dataType"="boolean",
     *        "required"=false,
     *        "description"=" Choose whether to return only tags linked to selected location or all tags linked to client. By default is false for NL locations and true for non-NL locations",
     *        "format"="?ignore_location=false"
     *      }
     *   },
     *   resource = true,
     *   description = "Delete a tag by id"
     * )
     * @Method("DELETE")
     * @Route("/{tag}")
     * @param Request $request
     * @param Tag $tag
     * @return array
     */
    public function deleteTag(Request $request, Tag $tag)
    {
        return $this->get(TagsService::class)->deleteTag($request, $tag);
    }
}