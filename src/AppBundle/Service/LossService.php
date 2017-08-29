<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareLossResponse;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;

class LossService extends DeclareControllerServiceBase
{
    /**
     * @param Request $request
     * @param $Id
     * @return JsonResponse
     */
    public function getLossById(Request $request, $Id)
    {
        $location = $this->getSelectedLocation($request);
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

        $log = ActionLogWriter::declareLossPost($this->getManager(), $client, $loggedInUser, $location, $content);

        //Client can only report a loss of own animals //TODO verify if animal belongs to UBN
        $animal = $content->get(Constant::ANIMAL_NAMESPACE);
        $isAnimalOfClient = $this->getManager()->getRepository(Animal::class)->verifyIfClientOwnsAnimal($client, $animal);

        if(!$isAnimalOfClient) {
            return new JsonResponse(array('code'=>428, "message" => "Animal doesn't belong to this account."), 428);
        }
        //Convert the array into an object and add the mandatory values retrieved from the database
        $messageObject = $this->buildMessageObject(RequestType::DECLARE_LOSS_ENTITY, $content, $client, $loggedInUser, $location);

        //First Persist object to Database, before sending it to the queue
        $this->persist($messageObject);
        $this->persistAnimalTransferringStateAndFlush($messageObject->getAnimal());

        //Send it to the queue and persist/update any changed state to the database
        $messageArray = $this->sendMessageObjectToQueue($messageObject);

        $this->saveNewestDeclareVersion($content, $messageObject);

        $log = ActionLogWriter::completeActionLog($this->getManager(), $log);

        $this->clearLivestockCacheForLocation($location);

        return new JsonResponse($messageArray, 200);
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

        //Client can only report a loss of own animals
        $animal = $content->get(Constant::ANIMAL_NAMESPACE);
        $isAnimalOfClient = $this->getManager()->getRepository(Animal::class)->verifyIfClientOwnsAnimal($client, $animal);

        if(!$isAnimalOfClient) {
            return new JsonResponse(array('code'=>428, "message" => "Animal doesn't belong to this account."), 428);
        }

        //Convert the array into an object and add the mandatory values retrieved from the database
        $declareLossUpdate = $this->buildMessageObject(RequestType::DECLARE_LOSS_ENTITY, $content, $client, $loggedInUser, $location);

        $entityManager = $this->getManager()->getRepository(Constant::DECLARE_LOSS_REPOSITORY);
        $messageObject = $entityManager->updateDeclareLossMessage($declareLossUpdate, $location, $Id);

        if($messageObject == null) {
            return new JsonResponse(array("message"=>"No DeclareLoss found with request_id: " . $Id), 204);
        }

        //Send it to the queue and persist/update any changed state to the database
        $messageArray = $this->sendEditMessageObjectToQueue($messageObject);
        $this->persistAnimalTransferringStateAndFlush($messageObject->getAnimal());

        //Persist object to Database
        $this->persist($messageObject);

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

        $declareLosses = $this->getManager()->getRepository(DeclareLossResponse::class)
            ->getLossesWithLastHistoryResponses($location);

        return ResultUtil::successResult($declareLosses);
    }
}