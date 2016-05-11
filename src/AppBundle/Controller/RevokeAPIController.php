<?php

namespace AppBundle\Controller;

use AppBundle\Component\MessageBuilderBase;
use AppBundle\Component\RevokeMessageBuilder;
use AppBundle\Constant\Constant;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Class RevokeAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/revokes")
 */
class RevokeAPIController extends APIController implements RevokeAPIControllerInterface
{

    /**
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("POST")
     */
    public function createRevoke(Request $request)
    {
        $content = $this->getContentAsArray($request);

        //Convert the array into an object and add the mandatory values retrieved from the database
        $messageObject = $this->buildMessageObject(RequestType::REVOKE_DECLARATION_ENTITY, $content, $this->getAuthenticatedUser($request));

        //First Persist object to Database, before sending it to the queue
        $this->persist($messageObject, RequestType::REVOKE_DECLARATION_ENTITY);

        //Send it to the queue and persist/update any changed state to the database
        $this->sendMessageObjectToQueue($messageObject, RequestType::REVOKE_DECLARATION_ENTITY, RequestType::REVOKE_DECLARATION);

        return new JsonResponse($messageObject, 200);
    }
}