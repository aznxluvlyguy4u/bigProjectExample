<?php

namespace AppBundle\Controller;

use AppBundle\Component\Utils;
use AppBundle\Enumerator\TagStateType;
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
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Post a new DeclareTagReplace request, containing a Tag to be replaced",
   *   input = "AppBundle\Entity\DeclareTagReplace",
   *   output = "AppBundle\Entity\DeclareTagReplaceResponse"
   * )
   * @param Request $request
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
  public function createTagReplaceRequest(Request $request)
  {
    $content = $this->getContentAsArray($request);
    $client = $this->getAuthenticatedUser($request);
    $location = $this->getSelectedLocation($request);

    $animal = $content->get(Constant::ANIMAL_NAMESPACE);
    $isAnimalOfClient = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY)->verifyIfClientOwnsAnimal($client, $animal);

    //Check if uln is valid
    if(!$isAnimalOfClient) {
      return new JsonResponse(array(Constant::CODE_NAMESPACE=>428, Constant::MESSAGE_NAMESPACE => "ANIMAL DOES NOT BELONG TO THIS ACCOUNT"), 428);
    }

    //Check if tag replacement is unassigned and in the database, else don't send any TagReplace
    $tagContent = $content->get(Constant::TAG_NAMESPACE);
    $validation = $this->getDoctrine()->getRepository(Constant::DECLARE_TAGS_TRANSFER_REPOSITORY)->validateTag($client, $tagContent[Constant::ULN_COUNTRY_CODE_NAMESPACE], $tagContent[Constant::ULN_NUMBER_NAMESPACE]);

    if($validation == null) {
      $errorMessage =  array(Constant::MESSAGE_NAMESPACE => "TAG IS NOT FOUND", Constant::CODE_NAMESPACE => 428);
      return new JsonResponse($errorMessage, 428);
    } else if($validation[Constant::VALIDITY_NAMESPACE] == false) {
        $tag = $validation[Constant::TAG_NAMESPACE];

        if($tag != null) {
          if($tag->getTagStatus() != TagStateType::UNASSIGNED){
            $errorMessage =  array(Constant::MESSAGE_NAMESPACE => "TAG IS NOT AVAILABLE FOR REPLACEMENT", Constant::CODE_NAMESPACE => 428);

            return new JsonResponse($errorMessage, 428);
          }
        } else {
          $errorMessage =  array(Constant::MESSAGE_NAMESPACE => "TAG IS NOT FOUND", Constant::CODE_NAMESPACE => 428);

          return new JsonResponse($errorMessage, 428);
        }
    }

    //Convert the array into an object and add the mandatory values retrieved from the database
    $declareTagReplace = $this->buildMessageObject(RequestType::DECLARE_TAG_REPLACE, $content, $client, $location);

    //First Persist object to Database, before sending it to the queue
    $this->persist($declareTagReplace);

    //Send it to the queue and persist/update any changed state to the database
    $messageArray = $this->sendMessageObjectToQueue($declareTagReplace);

    return new JsonResponse($messageArray, 200);
  }
}