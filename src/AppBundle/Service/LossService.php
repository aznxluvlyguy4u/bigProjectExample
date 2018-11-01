<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareLossResponse;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\DateUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Worker\DirectProcessor\DeclareLossProcessorInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;

class LossService extends DeclareControllerServiceBase
{
    /** @var DeclareLossProcessorInterface */
    private $lossProcessor;

    /**
     * @required
     *
     * @param DeclareLossProcessorInterface $lossProcessor
     */
    public function setLossProcessor(DeclareLossProcessorInterface $lossProcessor): void
    {
        $this->lossProcessor = $lossProcessor;
    }

    /**
     * @param Request $request
     * @param $Id
     * @return JsonResponse
     */
    public function getLossById(Request $request, $Id)
    {
        $location = $this->getSelectedLocation($request);
        $this->nullCheckLocation($location);

        $repository = $this->getManager()->getRepository(DeclareLoss::class);

        $loss = $repository->getLossByRequestId($location, $Id);

        return new JsonResponse($loss, 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getLosses(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $this->nullCheckLocation($location);

        $stateExists = $request->query->has(Constant::STATE_NAMESPACE);
        $repository = $this->getManager()->getRepository(DeclareLoss::class);

        if(!$stateExists) {
            $declareLosses = $repository->getLosses($location);

        } else if ($request->query->get(Constant::STATE_NAMESPACE) == Constant::HISTORY_NAMESPACE ) {

            $declareLosses = new ArrayCollection();
            foreach($repository->getLosses($location, RequestStateType::OPEN) as $loss) {
                $declareLosses->add($loss);
            }
            foreach($repository->getLosses($location, RequestStateType::REVOKING) as $loss) {
                $declareLosses->add($loss);
            }
            foreach($repository->getLosses($location, RequestStateType::FINISHED) as $loss) {
                $declareLosses->add($loss);
            }

        } else { //A state parameter was given, use custom filter to find subset
            $state = $request->query->get(Constant::STATE_NAMESPACE);
            $declareLosses = $repository->getLosses($location, $state);
        }

        return ResultUtil::successResult($declareLosses);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createLoss(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

        $this->nullCheckClient($client);
        $this->nullCheckLocation($location);

        $useRvoLogic = $location->isDutchLocation();

        $this->verifyIfClientOwnsAnimal($client, $content->get(Constant::ANIMAL_NAMESPACE));

        $log = ActionLogWriter::declareLossPost($this->getManager(), $client, $loggedInUser, $location, $content);

        //Convert the array into an object and add the mandatory values retrieved from the database
        $loss = $this->buildMessageObject(RequestType::DECLARE_LOSS_ENTITY, $content, $client, $loggedInUser, $location);

        if (!$useRvoLogic) {
            $this->validateNonRvoLoss($loss);
        }

        //First Persist object to Database, before sending it to the queue
        $this->persist($loss);
        $loss->getAnimal()->setTransferringTransferState();
        $this->getManager()->persist($loss->getAnimal());
        $this->getManager()->flush();

        $messageArray = $this->runDeclareLossWorkerLogic($loss);

        $this->saveNewestDeclareVersion($content, $loss);

        $log = ActionLogWriter::completeActionLog($this->getManager(), $log);

        $this->clearLivestockCacheForLocation($location);

        return new JsonResponse($messageArray, 200);
    }


    private function runDeclareLossWorkerLogic(DeclareLoss $loss)
    {
        if ($loss->isRvoMessage()) {
            //Send it to the queue and persist/update any changed state to the database
            return $this->sendMessageObjectToQueue($loss);
        }
        return $this->lossProcessor->process($loss);
    }


    /**
     * @param Request $request
     * @param $Id
     * @return JsonResponse
     */
    public function editLoss(Request $request, $Id)
    {
        $content = RequestUtil::getContentAsArray($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

        $this->nullCheckClient($client);
        $this->nullCheckLocation($location);

        $this->verifyIfClientOwnsAnimal($client, $content->get(Constant::ANIMAL_NAMESPACE));

        //Convert the array into an object and add the mandatory values retrieved from the database
        $declareLossUpdate = $this->buildMessageObject(RequestType::DECLARE_LOSS_ENTITY, $content, $client, $loggedInUser, $location);

        $entityManager = $this->getManager()->getRepository(Constant::DECLARE_LOSS_REPOSITORY);
        $messageObject = $entityManager->updateDeclareLossMessage($declareLossUpdate, $location, $Id);

        if($messageObject == null) {
            return new JsonResponse(array("message"=>"No DeclareLoss found with request_id: " . $Id), 204);
        }

        //Send it to the queue and persist/update any changed state to the database
        $messageArray = $this->sendEditMessageObjectToQueue($messageObject);

        $messageObject->getAnimal()->setTransferringTransferState();
        $this->getManager()->persist($messageObject->getAnimal());

        //Persist object to Database
        $this->persist($messageObject);
        $this->getManager()->flush();

        $this->clearLivestockCacheForLocation($location);

        return new JsonResponse($messageArray, 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getLossErrors(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $this->nullCheckLocation($location);

        $declareLosses = $this->getManager()->getRepository(DeclareLossResponse::class)->getLossesWithLastErrorResponses($location);

        return ResultUtil::successResult($declareLosses);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getLossHistory(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $this->nullCheckLocation($location);

        $declareLosses = $this->getManager()->getRepository(DeclareLossResponse::class)
            ->getLossesWithLastHistoryResponses($location);

        return ResultUtil::successResult($declareLosses);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function resendCreateLoss(Request $request)
    {
        $loggedInUser = $this->getUser();
        $isAdmin = AdminValidator::isAdmin($loggedInUser, AccessLevelType::DEVELOPER);
        if(!$isAdmin) { return AdminValidator::getStandardErrorResponse(); }

        $content = RequestUtil::getContentAsArray($request);
        $minDateOfDeathString = $content->get('min_date_of_death');
        $minDateOfDeath = new \DateTime($minDateOfDeathString);

        $requestMessages = $this->getManager()->getRepository(DeclareLoss::class)->findBy(
            ['requestState' => RequestStateType::OPEN]
        );

        $openCount = count($requestMessages);
        $resentCount = 0;

        if ($openCount > 0) {

            //Creating request succeeded, send to Queue
            /** @var DeclareLoss $requestMessage */
            foreach ($requestMessages as $requestMessage) {

                if ($requestMessage->getDateOfDeath() < $minDateOfDeath) {
                    continue;
                }

                $location = $requestMessage->getLocation();
                $client = $location ? $location->getOwner() : null;
                $loggedInUser = $requestMessage->getActionBy();

                $log = ActionLogWriter::declareLossPost($this->getManager(), $client, $loggedInUser,$location, $requestMessage);

                if($requestMessage->getResponses()->count() === 0) {
                    //Resend it to the queue and persist/update any changed state to the database
                    $result[] = $this->sendMessageObjectToQueue($requestMessage);
                    $resentCount++;
                }

                ActionLogWriter::completeActionLog($this->getManager(), $log);
            }
        }

        return new JsonResponse(['DeclareLoss' => ['found open declares' => $openCount, 'open declares resent' => $resentCount]], 200);
    }


    /**
     * @param DeclareLoss $loss
     */
    private function validateNonRvoLoss(DeclareLoss $loss)
    {
        $this->validateIfEventDateIsNotBeforeDateOfBirth($loss->getAnimal(), $loss->getDateOfDeath());
    }
}