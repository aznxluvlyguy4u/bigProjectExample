<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\Tag;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Output\DeclareReplaceTagsOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;


class TagReplaceService extends DeclareControllerServiceBase
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createTagReplaceRequest(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

        if(!$client) {
            return new JsonResponse("CLIENT NOT FOUND", 428);
        }

        $log = ActionLogWriter::declareTagReplacePost($this->getManager(), $client, $loggedInUser, $content);
        $animal = $content->get(Constant::ANIMAL_NAMESPACE);

        $isAnimalOfClient = $this->getManager()->getRepository(Animal::class)->verifyIfClientOwnsAnimal($client, $animal);

        //Check if uln is valid
        if(!$isAnimalOfClient) {
            return new JsonResponse("ANIMAL DOES NOT BELONG TO THIS ACCOUNT", 428);
        }

        //Check if tag replacement is unassigned and in the database, else don't send any TagReplace
        $tagContent = $content->get(Constant::TAG_NAMESPACE);
        $declareTagTransferRepository = $this->getManager()->getRepository(DeclareTagsTransfer::class);
        $validation = $declareTagTransferRepository->validateTag($client, $location,$tagContent[Constant::ULN_COUNTRY_CODE_NAMESPACE], $tagContent[Constant::ULN_NUMBER_NAMESPACE]);

        if($validation == null) {
            return new JsonResponse("TAG IS NOT FOUND", 428);
        } else if($validation[Constant::VALIDITY_NAMESPACE] == false) {
            /** @var Tag $tag */
            $tag = $validation[Constant::TAG_NAMESPACE];

            if($tag != null) {
                if($tag->getTagStatus() != TagStateType::UNASSIGNED){
                    return ResultUtil::errorResult("TAG IS NOT AVAILABLE FOR REPLACEMENT", 428);
                }
            } else {
                return ResultUtil::errorResult("TAG IS NOT FOUND", 428);
            }
        }

        //Set animal in mutating state, so a sync will not add animal

        //Convert the array into an object and add the mandatory values retrieved from the database
        $declareTagReplace = $this->buildMessageObject(RequestType::DECLARE_TAG_REPLACE, $content, $client, $loggedInUser, $location);

        //First Persist object to Database, before sending it to the queue
        $this->persist($declareTagReplace);

        //Send it to the queue and persist/update any changed state to the database
        $messageArray = $this->sendMessageObjectToQueue($declareTagReplace);

        $this->saveNewestDeclareVersion($content, $declareTagReplace);

        ActionLogWriter::completeActionLog($this->getManager(), $log);

        return new JsonResponse($messageArray, 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getTagReplaceHistory(Request $request)
    {
        $this->getAccountOwner($request);
        $location = $this->getSelectedLocation($request);

        $em = $this->getManager();
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
                WHERE (request_state = '".RequestStateType::OPEN."' OR
                      request_state = '".RequestStateType::REVOKING."' OR
                      request_state = '".RequestStateType::REVOKED."' OR
                      request_state = '".RequestStateType::FINISHED."' OR
                      request_state = '".RequestStateType::FINISHED_WITH_WARNING."') AND declare_tag_replace.location_id = '". $location->getId() ."'";
        $results = $em->getConnection()->query($sql)->fetchAll();

        $results = DeclareReplaceTagsOutput::createHistoryArray($results);

        return ResultUtil::successResult($results);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getTagReplaceErrors(Request $request)
    {
        $this->getAccountOwner($request);
        $location = $this->getSelectedLocation($request);

        $em = $this->getManager();
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
                WHERE declare_base.request_state = '".RequestStateType::FAILED."' AND declare_base.hide_failed_message = FALSE AND declare_tag_replace.location_id = '". $location->getId() ."'";
        $results = $em->getConnection()->query($sql)->fetchAll();

        $results = DeclareReplaceTagsOutput::createHistoryArray($results);

        return ResultUtil::successResult($results);
    }
}