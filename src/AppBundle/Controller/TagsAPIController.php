<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Tag;
use AppBundle\Entity\TagRepository;
use AppBundle\Enumerator\TagStateType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Enumerator\RequestType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Constant\Constant;

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
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Retrieve a Tag by its ulnCountryCode and ulnNumber, concatenated.",
   *   output = "AppBundle\Entity\Tag"
   * )
   * @param Request $request the request object
   * @param $Id
   * @return JsonResponse
   * @Route("/{Id}")
   * @Method("GET")
   */
  public function getTagById(Request $request, $Id)
  {
    $tagRepository = $this->getDoctrine()->getRepository(Constant::TAG_REPOSITORY);

    //validate if Id is of format: AZ123456789
    $isValidUlnFormat = $tagRepository->verifyUlnFormat($Id);
    if(!$isValidUlnFormat){
      return new JsonResponse(
          array("errorCode" => 428,
              "errorMessage" => "Given tagId format is invalid, supply tagId in the following format: AZ123456789"), 200);
    }

    $client = $this->getAuthenticatedUser($request);
    $tag = $tagRepository->findOneByString($client, $Id);

    if($tag == null) {
      return new JsonResponse(
          array("errorCode" => 400,
              "errorMessage" => "No tag found"), 200);
    } else {
      return new JsonResponse($tag, 200);
    }
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
   *   description = "Retrieve a list of Tags",
   *   output = "array"
   * )
   * @param Request $request
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getTags(Request $request)
  {
    $client = $this->getAuthenticatedUser($request);
    $location = $this->getSelectedLocation($request);
    /** @var TagRepository $tagRepository */
    $tagRepository = $this->getDoctrine()->getRepository(Tag::class);

    //No explicit filter given, thus find all
    if(!$request->query->has(Constant::STATE_NAMESPACE)) {
      //Only retrieve tags that are either assigned OR unassigned, ignore transferred tags
      $tags = $tagRepository->findTags($client, $location);
    } else { //A state parameter was given, use custom filter to find subset
      $tagStatus = $request->query->get(Constant::STATE_NAMESPACE);
      $tags = $tagRepository->findTags($client, $location, $tagStatus);
    }

    return new JsonResponse([Constant::RESULT_NAMESPACE => $tags], 200);
  }
}