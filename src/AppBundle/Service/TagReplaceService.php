<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareTagReplace;
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
use AppBundle\Worker\DirectProcessor\DeclareTagReplaceProcessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;


class TagReplaceService extends DeclareControllerServiceBase
{
    /** @var DeclareTagReplaceProcessorInterface */
    private $tagReplaceProcessor;

    /**
     * @required
     *
     * @param DeclareTagReplaceProcessorInterface $tagReplaceProcessor
     */
    public function setTagReplaceProcessor(DeclareTagReplaceProcessorInterface $tagReplaceProcessor): void
    {
        $this->tagReplaceProcessor = $tagReplaceProcessor;
    }

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

        $this->nullCheckClient($client);
        $this->nullCheckLocation($location);

        $useRvoLogic = $location->isDutchLocation();

        $log = ActionLogWriter::declareTagReplacePost($this->getManager(), $client, $loggedInUser, $content);

        $this->verifyIfClientOwnsAnimal($client, $content->get(Constant::ANIMAL_NAMESPACE));

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

        if ($useRvoLogic) {
            $this->validateNonRvoTagReplace($declareTagReplace);
        }

        //First Persist object to Database, before sending it to the queue
        $this->persist($declareTagReplace);


        if ($useRvoLogic) {
            //Send it to the queue and persist/update any changed state to the database
            $messageArray = $this->sendMessageObjectToQueue($declareTagReplace);

        } else {
            $messageArray = $this->tagReplaceProcessor->process($declareTagReplace);
        }

        $this->saveNewestDeclareVersion($content, $declareTagReplace);

        ActionLogWriter::completeActionLog($this->getManager(), $log);

        return new JsonResponse($messageArray, 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function resendOpenTagReplaceRequest(Request $request)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::DEVELOPER)) {
            return ResultUtil::unauthorized();
        }

        $content = RequestUtil::getContentAsArray($request);
        $minId = $content->get('min_id');

        if (!$minId) {
            return ResultUtil::errorResult('min_id missing', Response::HTTP_PRECONDITION_REQUIRED);
        }

        $openTagReplaces = $this->getManager()->getRepository(DeclareTagReplace::class)->findOpen($minId);

        /** @var DeclareTagReplace $declareTagReplace */
        foreach ($openTagReplaces as $declareTagReplace) {

            //Send it to the queue and persist/update any changed state to the database
            $messageArray = $this->sendMessageObjectToQueue($declareTagReplace);

        }

        return new JsonResponse(count($openTagReplaces), 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getTagReplaceHistory(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $this->nullCheckLocation($location);

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
        $location = $this->getSelectedLocation($request);
        $this->nullCheckLocation($location);

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


    /**
     * @param DeclareTagReplace $declareTagReplace
     */
    private function validateNonRvoTagReplace(DeclareTagReplace $declareTagReplace)
    {
        $animalWithReplacementTagUln = $this->getManager()->getRepository(Animal::class)
            ->findByUlnCountryCodeAndNumber(
                $declareTagReplace->getUlnCountryCodeReplacement(),
                $declareTagReplace->getUlnNumberReplacement()
            );

        if ($animalWithReplacementTagUln) {
            throw new PreconditionRequiredHttpException('AN ANIMAL ALREADY EXISTS WITH THIS ULN');
        }

        $this->validateIfEventDateIsNotBeforeDateOfBirth($declareTagReplace->getAnimal(),
            $declareTagReplace->getReplaceDate());
    }
}