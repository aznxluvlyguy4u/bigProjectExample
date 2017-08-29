<?php

namespace AppBundle\Controller;

use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\DeclareTagsTransferRepository;
use AppBundle\Entity\Tag;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Output\DeclareReplaceTagsOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Enumerator\RequestType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Constant\Constant;

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
      return $this->get('app.tag.replace')->createTagReplaceRequest($request);
  }

    /**
    * @param Request $request
    * @return JsonResponse
    * @Route("-history")
    * @Method("GET")
    */
    public function getTagReplaceHistory(Request $request)
    {
        return $this->get('app.tag.replace')->getTagReplaceHistory($request);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("-errors")
     * @Method("GET")
     */
    public function getTagReplaceErrors(Request $request)
    {
        return $this->get('app.tag.replace')->getTagReplaceErrors($request);
    }
}