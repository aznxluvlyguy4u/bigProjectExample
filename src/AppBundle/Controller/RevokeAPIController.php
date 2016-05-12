<?php

namespace AppBundle\Controller;

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
        $revokeDeclarationObject = $this->buildMessageObject(RequestType::REVOKE_DECLARATION_ENTITY, $content, $this->getAuthenticatedUser($request));

        //First Persist object to Database, before sending it to the queue
        $this->persist($revokeDeclarationObject, RequestType::REVOKE_DECLARATION_ENTITY);

        //TODO Maybe set the revoked requestState from the Internal Worker when a successful RevokeDeclaration has been received, and remove doing that in this controller. But doing it like this, might cause confusion for the user in the frontend.
        //Now set the requestState of the revoked message to REVOKED
        $this->persistRevokingRequestState($revokeDeclarationObject);

        //Send it to the queue and persist/update any changed state to the database
        $this->sendMessageObjectToQueue($revokeDeclarationObject, RequestType::REVOKE_DECLARATION_ENTITY, RequestType::REVOKE_DECLARATION);

        return new JsonResponse($revokeDeclarationObject, 200);
    }
}