<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
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
     *
     * Post a RevokeDeclaration request.
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
     *   description = "Post a RevokeDeclaration request",
     *   input = "AppBundle\Entity\RevokeDeclaration",
     *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
     * )
     *
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("POST")
     */
    public function createRevoke(Request $request)
    {
        $content = $this->getContentAsArray($request);
        $client = $this->getAuthenticatedUser($request);
        $location = $this->getSelectedLocation($request);

        //Validate if there is a message_number. It is mandatory for IenR
        $validation = $this->hasMessageNumber($content);
        if(!$validation['isValid']) {
            return new JsonResponse($validation[Constant::MESSAGE_NAMESPACE], $validation[Constant::CODE_NAMESPACE]);
        }

        //Convert the array into an object and add the mandatory values retrieved from the database
        $revokeDeclarationObject = $this->buildMessageObject(RequestType::REVOKE_DECLARATION_ENTITY, $content, $client, $location);

        //First Persist object to Database, before sending it to the queue
        $this->persist($revokeDeclarationObject);
        
        //Now set the requestState of the revoked message to REVOKED
        $this->persistRevokingRequestState($revokeDeclarationObject->getMessageNumber());

        //Send it to the queue and persist/update any changed state to the database
        $messageArray = $this->sendMessageObjectToQueue($revokeDeclarationObject);


        //If an Arrival of Import is revoked, add it to the LocationHealthQueue
        if($revokeDeclarationObject->getRequestTypeToRevoke() == RequestType::DECLARE_ARRIVAL_ENTITY) {
            $this->getHealthService()->updateLocationHealthQueue($revokeDeclarationObject->getArrival());

        } else if($revokeDeclarationObject->getRequestTypeToRevoke() == RequestType::DECLARE_IMPORT_ENTITY) {
            $this->getHealthService()->updateLocationHealthQueue($revokeDeclarationObject->getImport());
        }

        return new JsonResponse($messageArray, 200);
    }
}