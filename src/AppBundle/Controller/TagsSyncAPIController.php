<?php

namespace AppBundle\Controller;

use AppBundle\Util\RequestUtil;
use AppBundle\Util\Validator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Enumerator\RequestType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Constant\Constant;

/**
 * @Route("/api/v1/tags-sync")
 */
class TagsSyncAPIController extends APIController implements TagsSyncAPIControllerInterface
{

  /**
   *
   * Retrieve a RetrieveTags request, found by its ID.
   *
   * @ApiDoc(
   *   section = "Tag Syncs",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Retrieve a RetrieveTag request, found by its ID"
   * )
   * @param Request $request
   * @param int $Id Id of the RetrieveTags to be returned
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\RetrieveTagsRepository")
   * @Method("GET")
   */
  public function getRetrieveTagsById(Request $request, $Id)
  {
      return $this->get('app.tag.sync')->getRetrieveTagsById($request, $Id);
  }

  /**
   * Retrieve either a list of all RetrieveTags requests or a subset of RetrieveTags requests with a given state-type:
   * {
   *    OPEN,
   *    FINISHED,
   *    FAILED,
   *    CANCELLED
   * }
   *
   * @ApiDoc(
   *   section = "Tag Syncs",
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
   *        "description"=" RetrieveTags requests to filter on",
   *        "format"="?state=state-type"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a list of RetrieveTags"
   * )
   * @param Request $request
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getRetrieveTags(Request $request)
  {
      return $this->get('app.tag.sync')->getRetrieveTags($request);
  }

  /**
   *
   * Create a new RetrieveTags request
   *
   * @ApiDoc(
   *   section = "Tag Syncs",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Create a new RetrieveTags request"
   * )
   * @param Request $request
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
  public function createRetrieveTags(Request $request)
  {
      return $this->get('app.tag.sync')->createRetrieveTags($request);
  }
}