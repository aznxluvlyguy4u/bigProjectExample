<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Controller\TagsSyncAPIControllerInterface;
use AppBundle\Entity\RetrieveTags;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;


class TagSyncService extends DeclareControllerServiceBase implements TagsSyncAPIControllerInterface
{
    /**
     * @param Request $request
     * @param $Id
     * @return JsonResponse
     */
    public function getRetrieveTagsById(Request $request, $Id)
    {
        $retrieveTagsRequest = $this->getManager()->getRepository(RetrieveTags::class)->findOneBy(array(Constant::REQUEST_ID_NAMESPACE=>$Id));
        return new JsonResponse($retrieveTagsRequest, 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getRetrieveTags(Request $request)
    {
        //No explicit filter given, thus find all
        if(!$request->query->has(Constant::STATE_NAMESPACE)) {
            $retrieveEartags = $this->getManager()->getRepository(RetrieveTags::class)->findAll();
        } else { //A state parameter was given, use custom filter to find subset
            $state = $request->query->get(Constant::STATE_NAMESPACE);
            $retrieveEartags = $this->getManager()->getRepository(RetrieveTags::class)->findBy(array(Constant::REQUEST_STATE_NAMESPACE => $state));
        }

        return ResultUtil::successResult($retrieveEartags);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createRetrieveTags(Request $request)
    {
        //Get content to array
        $content = RequestUtil::getContentAsArray($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

        if($client == null) { return ResultUtil::errorResult('Client cannot be null', 428); }
        if($location == null) { return ResultUtil::errorResult('Location cannot be null', 428); }

        //Convert the array into an object and add the mandatory values retrieved from the database
        $retrieveEartagsRequest = $this->buildMessageObject(RequestType::RETRIEVE_TAGS_ENTITY, $content, $client, $loggedInUser, $location);

        //First Persist object to Database, before sending it to the queue
        $this->persist($retrieveEartagsRequest);

        //Send it to the queue and persist/update any changed state to the database
        $messageArray = $this->sendMessageObjectToQueue($retrieveEartagsRequest);

        return new JsonResponse($messageArray, 200);
    }
}