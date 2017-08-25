<?php


namespace AppBundle\Service;


use AppBundle\Cache\AnimalCacher;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\RequestMessageBuilder;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Controller\RevokeAPIControllerInterface;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\DeclareWeight;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Output\Output;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;


class RevokeService extends DeclareControllerServiceBase implements RevokeAPIControllerInterface
{
    /** @var EntityGetter */
    private $entityGetter;

    public function __construct(AwsExternalQueueService $externalQueueService,
                                CacheService $cacheService,
                                EntityManagerInterface $manager,
                                IRSerializer $irSerializer,
                                RequestMessageBuilder $requestMessageBuilder,
                                UserService $userService,
                                EntityGetter $entityGetter)
    {
        parent::__construct($externalQueueService, $cacheService, $manager, $irSerializer, $requestMessageBuilder, $userService);

        $this->entityGetter = $entityGetter;
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createRevoke(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);
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

        $log = ActionLogWriter::revokePost($this->getManager(), $client, $loggedInUser, $revokeDeclarationObject);

        //First Persist object to Database, before sending it to the queue
        $this->persist($revokeDeclarationObject);

        //Now set the requestState of the revoked message to REVOKED
        $this->persistRevokingRequestState($this->entityGetter, $revokeDeclarationObject->getMessageNumber());

        //Send it to the queue and persist/update any changed state to the database
        $messageArray = $this->sendMessageObjectToQueue($revokeDeclarationObject);

        $log = ActionLogWriter::completeActionLog($this->getManager(), $log);

        return new JsonResponse($messageArray, 200);
    }


    /**
     * @param ArrayCollection $content
     * @return array
     */
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
     * @param Request $request
     * @param $messageId
     * @return JsonResponse
     */
    public function revokeNsfoDeclaration(Request $request, $messageId)
    {
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();

        $log = ActionLogWriter::revokeNsfoDeclaration($this->getManager(), $client, $loggedInUser, $messageId);

        $declarationFromMessageId = Validator::isNonRevokedNsfoDeclarationOfClient($this->getManager(), $client, $messageId);

        if(!($declarationFromMessageId instanceof DeclareNsfoBase)) {
            return Output::createStandardJsonErrorResponse();
        }

        $nsfoDeclaration = self::revoke($declarationFromMessageId, $loggedInUser);
        $this->persistAndFlush($nsfoDeclaration);

        $output = 'Revoke complete';

        if($nsfoDeclaration instanceof DeclareWeight) {
            AnimalCacher::cacheWeightByAnimal($this->getManager(), $nsfoDeclaration->getAnimal());
        }

        $log = ActionLogWriter::completeActionLog($this->getManager(), $log);

        return ResultUtil::successResult($output);
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