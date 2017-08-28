<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\Location;
use AppBundle\Entity\TagTransferItemResponse;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;


class TagTransferService extends DeclareControllerServiceBase
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createTagsTransfer(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

        $log = ActionLogWriter::declareTagTransferPost($this->getManager(), $client, $loggedInUser, $content);

        //Validate if ubn is in database and retrieve the relationNumberKeeper owning that ubn
        $ubnVerification = $this->isUbnValid($content->get(Constant::UBN_NEW_OWNER_NAMESPACE));
        if(!$ubnVerification['isValid']) {
            $code = $ubnVerification[Constant::CODE_NAMESPACE];
            $message = $ubnVerification[Constant::MESSAGE_NAMESPACE];
            return new JsonResponse(array("code" => $code, "message" => $message), $code);
        }
        $content->set("relation_number_acceptant", $ubnVerification['relationNumberKeeper']);

        //TODO Phase 2, with history and error tab in front-end, we can do a less strict filter. And only remove the incorrect tags and process the rest. However for proper feedback to the client we need to show the successful and failed TagTransfer history.

        //Check if ALL tags are unassigned and in the database, else don't send any TagTransfer

        $repository = $this->getManager()->getRepository(DeclareTagsTransfer::class);
        $validation = $repository->validateTags($client, $location, $content);
        if($validation[Constant::IS_VALID_NAMESPACE] == false) {
            return new JsonResponse($validation[Constant::MESSAGE_NAMESPACE], $validation[Constant::CODE_NAMESPACE]);
        }

        //Convert the array into an object and add the mandatory values retrieved from the database
        $declareTagsTransfer = $this->buildMessageObject(RequestType::DECLARE_TAGS_TRANSFER_ENTITY, $content, $client, $loggedInUser, $location);

        //First Persist object to Database, before sending it to the queue
        $this->persist($declareTagsTransfer);

        //Send it to the queue and persist/update any changed state to the database
        $messageArray = $this->sendMessageObjectToQueue($declareTagsTransfer);

        $log = ActionLogWriter::completeActionLog($this->getManager(), $log);

        return new JsonResponse($messageArray, 200);
    }


    /**
     * @param string $ubn
     * @return array
     */
    public function isUbnValid($ubn)
    {
        //Default values
        $isValid = false;
        $relationNumberKeeper = null;
        $code = 428;
        $message = 'THE UBN IS NOT REGISTERED AT NSFO';

        $location = $this->getManager()->getRepository(Location::class)->findOneByActiveUbn($ubn);

        if($location != null) {
            $isValid = true;
            //'relationNumberKeeper' is an obligatory field in Client, so no need to verify if that field exists or not.
            $relationNumberKeeper = $location->getCompany()->getOwner()->getRelationNumberKeeper();
            $code = 200;
            $message = 'UBN IS VALID';
        } //else just use the default values

        return array('isValid' => $isValid,
            'relationNumberKeeper' => $relationNumberKeeper,
            Constant::MESSAGE_NAMESPACE => $message,
            Constant::CODE_NAMESPACE => $code
        );

    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getTagTransferItemErrors(Request $request)
    {
        $client = $this->getAccountOwner($request);
        $location = $this->getSelectedLocation($request);

        $tagTransfers = $this->getManager()->getRepository(TagTransferItemResponse::class)
            ->getTagTransferItemRequestsWithLastErrorResponses($client, $location);

        return ResultUtil::successResult($tagTransfers);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getTagTransferItemHistory(Request $request)
    {
        $client = $this->getAccountOwner($request);
        $location = $this->getSelectedLocation($request);
        $tagTransfers = $this->getManager()->getRepository(TagTransferItemResponse::class)->getTagTransferItemRequestsWithLastHistoryResponses($client, $location);

        return ResultUtil::successResult($tagTransfers);
    }
}