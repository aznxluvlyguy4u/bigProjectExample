<?php

namespace AppBundle\Controller;

use AppBundle\Component\Utils;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\DeclareTagsTransferRepository;
use AppBundle\Entity\Tag;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Output\DeclareReplaceTagsOutput;
use AppBundle\Output\DeclareTagsTransferResponseOutput;
use AppBundle\Util\ActionLogWriter;
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
    $om = $this->getDoctrine()->getManager();
    
    $content = $this->getContentAsArray($request);
    $client = $this->getAuthenticatedUser($request);
    $loggedInUser = $this->getLoggedInUser($request);
    $location = $this->getSelectedLocation($request);

    $log = ActionLogWriter::declareTagReplacePost($om, $client, $loggedInUser, $content);
    
    $animal = $content->get(Constant::ANIMAL_NAMESPACE);
    $isAnimalOfClient = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY)->verifyIfClientOwnsAnimal($client, $animal);

    //Check if uln is valid
    if(!$isAnimalOfClient) {
      return new JsonResponse(array(Constant::CODE_NAMESPACE=>428, Constant::MESSAGE_NAMESPACE => "ANIMAL DOES NOT BELONG TO THIS ACCOUNT"), 428);
    }

    //Check if tag replacement is unassigned and in the database, else don't send any TagReplace
    $tagContent = $content->get(Constant::TAG_NAMESPACE);
    /** @var DeclareTagsTransferRepository $declareTagTransferRepository */
    $declareTagTransferRepository = $this->getDoctrine()->getRepository(DeclareTagsTransfer::class);
    $validation = $declareTagTransferRepository->validateTag($client, $location,$tagContent[Constant::ULN_COUNTRY_CODE_NAMESPACE], $tagContent[Constant::ULN_NUMBER_NAMESPACE]);

    if($validation == null) {
      $errorMessage =  array(Constant::MESSAGE_NAMESPACE => "TAG IS NOT FOUND", Constant::CODE_NAMESPACE => 428);
      return new JsonResponse($errorMessage, 428);
    } else if($validation[Constant::VALIDITY_NAMESPACE] == false) {
        /** @var Tag $tag */
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
    $declareTagReplace = $this->buildMessageObject(RequestType::DECLARE_TAG_REPLACE, $content, $client, $loggedInUser, $location);

    //First Persist object to Database, before sending it to the queue
    $this->persist($declareTagReplace);

    //Send it to the queue and persist/update any changed state to the database
    $messageArray = $this->sendMessageObjectToQueue($declareTagReplace);

    $log = ActionLogWriter::completeActionLog($om, $log);
    
    return new JsonResponse($messageArray, 200);
  }

    /**
    * @param Request $request
    * @return JsonResponse
    * @Route("-history")
    * @Method("GET")
    */
    public function getTagReplaceHistory(Request $request)
    {
        $this->getAuthenticatedUser($request);
        $location = $this->getSelectedLocation($request);

        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT
                  declare_tag_replace.replace_date,
                  declare_tag_replace.uln_country_code_to_replace,
                  declare_tag_replace.uln_number_to_replace,
                  declare_tag_replace.animal_order_number_to_replace,
                  declare_tag_replace.uln_country_code_replacement,
                  declare_tag_replace.uln_number_replacement,
                  declare_tag_replace.animal_order_number_replacement,
                  declare_base.request_id,
                  declare_base.request_state,
                  declare_base_response.message_number
                FROM
                  declare_tag_replace
                INNER JOIN declare_base ON declare_tag_replace.id = declare_base.id
                LEFT JOIN declare_tag_replace_response ON declare_tag_replace.id = declare_tag_replace_response.declare_tag_replace_request_message_id
                LEFT JOIN declare_base_response ON declare_tag_replace_response.id = declare_base_response.id
                WHERE declare_base.request_state <> 'FAILED' AND declare_tag_replace.location_id = '". $location->getId() ."'";
        $results = $em->getConnection()->query($sql)->fetchAll();

        $results = DeclareReplaceTagsOutput::createHistoryArray($results);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $results), 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("-errors")
     * @Method("GET")
     */
    public function getTagReplaceErrors(Request $request)
    {
        $this->getAuthenticatedUser($request);
        $location = $this->getSelectedLocation($request);

        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT
                  declare_tag_replace.replace_date,
                  declare_tag_replace.uln_country_code_to_replace,
                  declare_tag_replace.uln_number_to_replace,
                  declare_tag_replace.animal_order_number_to_replace,
                  declare_tag_replace.uln_country_code_replacement,
                  declare_tag_replace.uln_number_replacement,
                  declare_tag_replace.animal_order_number_replacement,
                  declare_base.request_id,
                  declare_base.request_state,
                  declare_base_response.message_number
                FROM
                  declare_tag_replace
                INNER JOIN declare_base ON declare_tag_replace.id = declare_base.id
                LEFT JOIN declare_tag_replace_response ON declare_tag_replace.id = declare_tag_replace_response.declare_tag_replace_request_message_id
                LEFT JOIN declare_base_response ON declare_tag_replace_response.id = declare_base_response.id
                WHERE declare_base.request_state = 'FAILED' AND declare_tag_replace.location_id = '". $location->getId() ."'";
        $results = $em->getConnection()->query($sql)->fetchAll();

        $results = DeclareReplaceTagsOutput::createHistoryArray($results);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $results), 200);
    }
}