<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\Location;
use AppBundle\Entity\TagTransferItemResponse;
use AppBundle\Enumerator\RequestType;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Worker\DirectProcessor\DeclareTagTransferProcessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;


class TagTransferService extends DeclareControllerServiceBase
{
    /** @var DeclareTagTransferProcessorInterface */
    private $tagTransferProcessor;

    /**
     * @required
     *
     * @param DeclareTagTransferProcessorInterface $tagTransferProcessor
     */
    public function setTagTransferProcessor(DeclareTagTransferProcessorInterface $tagTransferProcessor): void
    {
        $this->tagTransferProcessor = $tagTransferProcessor;
    }

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

        $this->validateIfLocationIsDutch($location,DeclareTagsTransfer::class);

        //Validate if ubn is in database and retrieve the relationNumberKeeper owning that ubn
        $locationNewOwner = $this->getValidatedLocationNewOwner($content->get(Constant::UBN_NEW_OWNER_NAMESPACE), $location);
        $content->set('relation_number_acceptant', $locationNewOwner->getOwner()->getRelationNumberKeeper());

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
     * @param string $ubnNewOwner
     * @param Location $loggedInLocation
     * @return Location
     */
    public function getValidatedLocationNewOwner($ubnNewOwner, Location $loggedInLocation)
    {
        $errorMessage = '';

        $locationNewOwner = $this->getManager()->getRepository(Location::class)->findOneByActiveUbn($ubnNewOwner);

        if (!$locationNewOwner) {
            $errorMessage .= ucfirst(strtolower($this->translator->trans('THE UBN IS NOT REGISTERED AT NSFO'))).'. ';

        } else {
            if ($loggedInLocation && $loggedInLocation->getUbn()
            && $loggedInLocation->getUbn() === $locationNewOwner->getUbn())
            {
                $errorMessage .= ucfirst(strtolower($this->translator->trans('UBN NEW OWNER CANNOT BE SAME AS LOGGED IN UBN'))).'. ';
            }

            $this->validateIfOriginAndDestinationAreInSameCountry(DeclareTagsTransfer::class,
                $loggedInLocation, $locationNewOwner);

            if (!$locationNewOwner->getOwner() || empty($locationNewOwner->getOwner()->getRelationNumberKeeper())) {
                //'relationNumberKeeper' is an obligatory field in Client, so no need to verify if that field exists or not.
                $errorMessage .= ucfirst(strtolower($this->translator->trans('THE NEW OWNER HAS NO RELATION NUMBER KEEPER IN THE NSFO SYSTEM'))).'. ';
            }
        }

        if (!empty($errorMessage)) {
            throw new PreconditionFailedHttpException($errorMessage);
        }

        return $locationNewOwner;
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