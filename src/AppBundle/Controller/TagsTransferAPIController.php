<?php

namespace AppBundle\Controller;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\TagTransferService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TransferTagsAPI
 * @Route("/api/v1/tags-transfers")
 */
class TagsTransferAPIController extends APIController implements TagsTransferAPIControllerInterface
{

  /**
   *
   * Create a new DeclareTagsTransfer request for multiple Tags
   *
   * @ApiDoc(
   *   section = "Tag Transfers",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Post a new DeclareTagsTransfer request, containing multiple Tags to be transferred"
   * )
   * @param Request $request
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
  public function createTagsTransfer(Request $request)
  {
      return $this->get(TagTransferService::class)->createTagsTransfer($request);
  }


  /**
   *
   * Get TagTransferItemRequests which have failed last responses.
   *
   * @ApiDoc(
   *   section = "Tag Transfers",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get TagTransferItemRequests which have failed last responses"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-errors")
   * @Method("GET")
   */
  public function getTagTransferItemErrors(Request $request)
  {
      return $this->get(TagTransferService::class)->getTagTransferItemErrors($request);
  }


  /**
   *
   * For the history view, get TagTransferItemRequests which have the following requestState:
   * OPEN, REVOKING, REVOKED, FINISHED or FINISHED_WITH_WARNING.
   *
   * @ApiDoc(
   *   section = "Tag Transfers",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get TagTransferItemRequests which have the following requestState: OPEN, REVOKING, REVOKED, FINISHED or FINISHED_WITH_WARNING"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-history")
   * @Method("GET")
   */
  public function getTagTransferItemHistory(Request $request)
  {
      return $this->get(TagTransferService::class)->getTagTransferItemHistory($request);
  }
}