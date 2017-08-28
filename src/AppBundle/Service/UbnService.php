<?php

namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Processor;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Output\ProcessorOutput;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;


class UbnService extends DeclareControllerServiceBase
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getUBNDetails(Request $request)
    {
        //Get content to array
        $content = RequestUtil::getContentAsArray($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

        //Convert the array into an object and add the mandatory values retrieved from the database
        $messageObject = $this->buildMessageObject(RequestType::RETRIEVE_UBN_DETAILS_ENTITY, $content, $client, $loggedInUser, $location);

        //First Persist object to Database, before sending it to the queue
        $this->persist($messageObject);

        //Send it to the queue and persist/update any changed state to the database
        $this->sendMessageObjectToQueue($messageObject);

        return new JsonResponse($messageObject, 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getUbnProcessors(Request $request)
    {
        $processors = $this->getManager()->getRepository(Processor::class)->findAll();
        $includeNames = true;
        $output = ProcessorOutput::create($processors, $includeNames);
        return new JsonResponse($output, 200);
    }
}