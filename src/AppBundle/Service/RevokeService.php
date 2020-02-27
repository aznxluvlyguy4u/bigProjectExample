<?php


namespace AppBundle\Service;


use AppBundle\Cache\AnimalCacher;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\RevokeAPIControllerInterface;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\BasicRvoDeclareInterface;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\DeclareBaseInterface;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\DeclareWeight;
use AppBundle\Entity\Mate;
use AppBundle\Entity\RelocationDeclareInterface;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Exception\DeadAnimalHttpException;
use AppBundle\Output\Output;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\Validator;
use AppBundle\Worker\DirectProcessor\RevokeProcessorInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;


class RevokeService extends DeclareControllerServiceBase implements RevokeAPIControllerInterface
{
    /** @var EntityGetter */
    private $entityGetter;

    /** @var RevokeProcessorInterface */
    private $revokeProcessor;

    /**
     * @required
     *
     * @param EntityGetter $entityGetter
     */
    public function setEntityGetter($entityGetter)
    {
        $this->entityGetter = $entityGetter;
    }

    /**
     * @required
     *
     * @param RevokeProcessorInterface $revokeProcessor
     */
    public function setRevokeProcessor(RevokeProcessorInterface $revokeProcessor): void
    {
        $this->revokeProcessor = $revokeProcessor;
    }


    /**
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function createRevoke(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        if ($location->isDutchLocation()) {
            return $this->createRvoRevoke($request);
        }
        return $this->processNonRvoRevoke($request);
    }


    /**
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    private function createRvoRevoke(Request $request)
    {
        $content = RequestUtil::getContentAsArrayCollection($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

        $this->hasMessageNumber($content);

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

        return $messageArray;
    }


    /**
     * @param Request $request
     * @return array|bool
     */
    private function processNonRvoRevoke(Request $request)
    {
        $content = RequestUtil::getContentAsArrayCollection($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();

        $requestId = $content->get(JsonInputConstant::REQUEST_ID);
        $declare = $this->getRequestByRequestId($requestId);
        $isAlreadyRevoked = $declare instanceof BasicRvoDeclareInterface && $declare->isRevoked();

        switch (true) {
            case $declare instanceof DeclareArrival:
                if ($isAlreadyRevoked) {
                    $revoke = $this->getAlreadyRevokedResponse($declare);
                } else {
                    $this->validateRelocationDeclareRevoke($declare);
                    $revoke = $this->revokeProcessor->revokeArrival($declare, $client, $loggedInUser);
                }
                break;

            case $declare instanceof DeclareDepart:
                if ($isAlreadyRevoked) {
                    $revoke = $this->getAlreadyRevokedResponse($declare);
                } else {
                    $this->validateRelocationDeclareRevoke($declare);
                    $revoke = $this->revokeProcessor->revokeDepart($declare, $client, $loggedInUser);
                }
                break;

            case $declare instanceof DeclareExport:
                $this->validateRelocationDeclareRevoke($declare);
                $revoke = $this->revokeProcessor->revokeExport($declare, $client, $loggedInUser);
                break;

            case $declare instanceof DeclareImport:
                $this->validateRelocationDeclareRevoke($declare);
                $revoke = $this->revokeProcessor->revokeImport($declare, $client, $loggedInUser);
                break;

            case $declare instanceof DeclareLoss:
                $this->validateDeclareForRevoke($declare);
                $revoke = $this->revokeProcessor->revokeLoss($declare, $client, $loggedInUser);
                break;

            case $declare instanceof DeclareTagReplace:
                $this->validateTagReplaceRevoke($declare);
                $revoke = $this->revokeProcessor->revokeTagReplace($declare, $client, $loggedInUser);
                break;

            default: throw new PreconditionRequiredHttpException(
                'Non-NL revoke is not allowed for this declare type: '.Utils::getClassName($declare));
        }

        if ($isAlreadyRevoked) {
            ActionLogWriter::nonRvoRevoke($this->getManager(), $client, $loggedInUser, $revoke);
        }

        return is_bool($revoke) ? $revoke : $this->getDeclareMessageArray($revoke,false);
    }


    /**
     * @param ArrayCollection $content
     */
    public function hasMessageNumber(ArrayCollection $content): void
    {
        $messageNumber = $content->get(Constant::MESSAGE_NUMBER_SNAKE_CASE_NAMESPACE);
        if (empty($messageNumber)) {
            $messageBody = ucfirst(strtolower($this->translator->trans('THE MESSAGE NUMBER IS MISSING AND THEREFORE THE DECLARE CANNOT BE REVOKED')) . '.');
            throw new PreconditionRequiredHttpException($messageBody);
        }
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

        $declarationFromMessageId = Validator::isNonRevokedNsfoDeclarationOfClient($this->getManager(), $client, $messageId, $loggedInUser);

        if(!($declarationFromMessageId instanceof DeclareNsfoBase)) {
            return Output::createStandardJsonErrorResponse();
        }

        $nsfoDeclaration = self::revoke($declarationFromMessageId, $loggedInUser);
        $this->persistAndFlush($nsfoDeclaration);

        $output = 'Revoke complete';

        if($nsfoDeclaration instanceof DeclareWeight) {
            AnimalCacher::cacheWeightByAnimal($this->getManager(), $nsfoDeclaration->getAnimal());
        }

        if($nsfoDeclaration instanceof Mate) {
            $this->getManager()->getRepository(Animal::class)->purgeCandidateMothersCache($nsfoDeclaration->getLocation(), $this->getCacheService());
            AnimalRepository::purgeEwesLivestockWithLastMateCache($nsfoDeclaration->getLocation(), $this->getCacheService());
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


    /**
     * @param DeclareBaseInterface $declare
     */
    public function validateDeclareForRevoke(DeclareBaseInterface $declare): void
    {
        $requestState = $declare->getRequestState();

        if (
            $requestState === RequestStateType::FINISHED ||
            $requestState === RequestStateType::FINISHED_WITH_WARNING
        ) {
            return;
        }

        if ($requestState === RequestStateType::REVOKED) {
            throw new PreconditionRequiredHttpException($this->translator->trans('DECLARE HAS ALREADY BEEN REVOKED'));
        }

        if ($requestState === RequestStateType::REVOKING) {
            throw new PreconditionRequiredHttpException($this->translator->trans('DECLARE IS BEING REVOKED'));
        }

        throw new PreconditionRequiredHttpException(
            $this->translator->trans('A DECLARE WITH THIS REQUEST STATE CANNOT BE REVOKED').': '.
            $this->translator->trans($requestState)
        );
    }


    /**
     * @param DeclareBase $declare
     * @return RevokeDeclaration|true
     */
    public function getAlreadyRevokedResponse(DeclareBase $declare)
    {
        if ($declare instanceof BasicRvoDeclareInterface && $declare->getRevoke()) {
            return $declare->getRevoke();
        }
        return true;
    }


    /**
     * @param DeclareTagReplace $tagReplace
     */
    private function validateTagReplaceRevoke(DeclareTagReplace $tagReplace)
    {
        $this->validateDeclareForRevoke($tagReplace);
        $this->validateIfAnimalIsAlive($tagReplace->getAnimal());
    }


    /**
     * @param RelocationDeclareInterface $declare
     */
    private function validateRelocationDeclareRevoke(RelocationDeclareInterface $declare)
    {
        $this->validateDeclareForRevoke($declare);
        $this->validateIfAnimalIsAlive($declare->getAnimal());
    }


    /**
     * @param Animal|null $animal
     */
    private function validateIfAnimalIsAlive(?Animal $animal)
    {
        if ($animal && $animal->isDead()) {
            throw new DeadAnimalHttpException($this->translator, $animal->getUln());
        }
    }
}
