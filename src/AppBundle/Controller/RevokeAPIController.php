<?php

namespace AppBundle\Controller;

use AppBundle\Cache\AnimalCacher;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\DeclareWeight;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Output\Output;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\Validator;
use AppBundle\Validation\DeclareNsfoBaseValidator;
use Doctrine\Common\Collections\ArrayCollection;
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
     *   section = "Revokes",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Post a RevokeDeclaration request"
     * )
     *
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("POST")
     */
    public function createRevoke(Request $request)
    {
        $om = $this->getDoctrine()->getManager();

        $content = $this->getContentAsArray($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

        //Validate if there is a message_number. It is mandatory for IenR
        $validation = $this->hasMessageNumber($content);
        if(!$validation['isValid']) {
            return new JsonResponse($validation[Constant::MESSAGE_NAMESPACE], $validation[Constant::CODE_NAMESPACE]);
        }

        //Convert the array into an object and add the mandatory values retrieved from the database
        $revokeDeclarationObject = $this->buildMessageObject(RequestType::REVOKE_DECLARATION_ENTITY, $content, $client, $loggedInUser, $location);

        $log = ActionLogWriter::revokePost($om, $client, $loggedInUser, $revokeDeclarationObject);

        //First Persist object to Database, before sending it to the queue
        $this->persist($revokeDeclarationObject);
        
        //Now set the requestState of the revoked message to REVOKED
        $this->persistRevokingRequestState($revokeDeclarationObject->getMessageNumber());

        //Send it to the queue and persist/update any changed state to the database
        $messageArray = $this->sendMessageObjectToQueue($revokeDeclarationObject);

        $log = ActionLogWriter::completeActionLog($om, $log);

        return new JsonResponse($messageArray, 200);
    }


    public function hasMessageNumber(ArrayCollection $content)
    {
        //Default values
        $isValid = false;
        $messageNumber = null;
        $code = 428;
        $messageBody = 'THERE IS NO VALUE GIVEN FOR THE MESSAGE NUMBER';

        if($content->containsKey(Constant::MESSAGE_NUMBER_SNAKE_CASE_NAMESPACE)) {
            $messageNumber = $content->get(Constant::MESSAGE_NUMBER_SNAKE_CASE_NAMESPACE);

            if($messageNumber != null || $messageNumber != "") {
                $isValid = true;
                $code = 200;
                $messageBody = 'MESSAGE NUMBER FIELD EXISTS AND IS NOT EMPTY';
            }
        }

        return Utils::buildValidationArray($isValid, $code, $messageBody, array('messageNumber' => $messageNumber));
    }


    /**
     *
     * Revoke non-IR declarations
     *
     * @ApiDoc(
     *   section = "Revokes",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Revoke Mate"
     * )
     *
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-nsfo/{messageId}")
     * @Method("PUT")
     */
    public function revokeNsfoDeclaration(Request $request, $messageId)
    {
        $manager = $this->getDoctrine()->getManager();
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();

        $log = ActionLogWriter::revokeNsfoDeclaration($manager, $client, $loggedInUser, $messageId);

        $declarationFromMessageId = Validator::isNonRevokedNsfoDeclarationOfClient($manager, $client, $messageId);

        if(!($declarationFromMessageId instanceof DeclareNsfoBase)) {
            return Output::createStandardJsonErrorResponse();
        }

        $nsfoDeclaration = self::revoke($declarationFromMessageId, $loggedInUser);
        $this->persistAndFlush($nsfoDeclaration);

        $output = 'Revoke complete';

        if($nsfoDeclaration instanceof DeclareWeight) {
            AnimalCacher::cacheWeightByAnimal($manager, $nsfoDeclaration->getAnimal());
        }

        $log = ActionLogWriter::completeActionLog($manager, $log);

        return new JsonResponse([JsonInputConstant::RESULT => $output], 200);
    }


    /**
     * @param DeclareNsfoBase $declareNsfoBase
     * @return DeclareNsfoBase
     */
    public static function revoke(DeclareNsfoBase $declareNsfoBase, $loggedInUser)
    {
        if($declareNsfoBase instanceof DeclareWeight) {
            if($declareNsfoBase->getWeightMeasurement() != null) {
                $declareNsfoBase->getWeightMeasurement()->setIsRevoked(true);
                $declareNsfoBase->getWeightMeasurement()->setIsActive(false);
            }
        }

        $declareNsfoBase->setRequestState(RequestStateType::REVOKED);
        $declareNsfoBase->setRevokeDate(new \DateTime('now'));
        $declareNsfoBase->setRevokedBy($loggedInUser);
        return $declareNsfoBase;
    }
}