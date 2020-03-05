<?php


namespace AppBundle\Service;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\UpdateAnimalDataWorker;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\UpdateType;
use AppBundle\Enumerator\WorkerAction;
use AppBundle\Enumerator\WorkerType;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\UlnValidatorInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Enqueue\Client\ProducerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

class TaskService
{
    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var BaseSerializer
     */
    private $serializer;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Logger
     */
    private $logger;

    /** @var UlnValidatorInterface */
    private $ulnValidator;

    /**
     * TaskService constructor.
     * @param ProducerInterface $producer
     * @param BaseSerializer $serializer
     * @param EntityManager $em
     * @param UserService $userService
     * @param TranslatorInterface $translator
     * @param Logger $logger
     * @param UlnValidatorInterface $ulnValidator
     */
    public function __construct(
        ProducerInterface $producer,
        BaseSerializer $serializer,
        EntityManager $em,
        UserService $userService,
        TranslatorInterface $translator,
        Logger $logger,
        UlnValidatorInterface $ulnValidator
    )
    {
        $this->em = $em;
        $this->producer = $producer;
        $this->serializer = $serializer;
        $this->userService = $userService;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->ulnValidator = $ulnValidator;
    }

    /**
     * @param Request $request
     * @return array|null
     * @throws \Exception
     */
    public function getTasks(Request $request): ?array
    {
        $user = $this->userService->getUser();
        $accountOwner = $this->userService->getAccountOwner($request);

        $workers = $this->em->getRepository(UpdateAnimalDataWorker::class)->getTasks($user, $accountOwner);
        return $this->serializer->getDecodedJson($workers,[JmsGroup::BASIC],true);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function createStarEwesCalculationTask(Request $request)
    {
        if(!AdminValidator::isAdmin($this->userService->getUser(), AccessLevelType::SUPER_ADMIN)) { //validate if user is at least a SUPER_ADMIN
            return AdminValidator::getStandardErrorResponse();
        }

        $inputForHash = UpdateType::STAR_EWES;

        return $this->processTaskAsWorkerTask(
            [],
            $request,UpdateType::STAR_EWES, $inputForHash
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function createInbreedingCoefficientCalculationTask(Request $request)
    {
        if(!AdminValidator::isAdmin($this->userService->getUser(), AccessLevelType::SUPER_ADMIN)) { //validate if user is at least a SUPER_ADMIN
            return AdminValidator::getStandardErrorResponse();
        }

        $inputForHash = UpdateType::INBREEDING_COEFFICIENT_CALCULATION;

        return $this->processTaskAsWorkerTask(
            [],
            $request,UpdateType::INBREEDING_COEFFICIENT_CALCULATION, $inputForHash
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function createInbreedingCoefficientRecalculationTask(Request $request)
    {
        if(!AdminValidator::isAdmin($this->userService->getUser(), AccessLevelType::SUPER_ADMIN)) { //validate if user is at least a SUPER_ADMIN
            return AdminValidator::getStandardErrorResponse();
        }

        $inputForHash = UpdateType::INBREEDING_COEFFICIENT_RECALCULATION;

        return $this->processTaskAsWorkerTask(
            [],
            $request,UpdateType::INBREEDING_COEFFICIENT_RECALCULATION, $inputForHash
        );
    }

    private function processTaskAsWorkerTask(array $messageBodyAsArray, Request $request, string $updateType, string $inputForHash)
    {
        $workerId = null;
        try {

            if ($this->isSimilarNonExpiredTaskAlreadyInProgress($request, $updateType, $inputForHash)) {
                return $this->updateWorkerInProgressAlreadyExistErrorResponse();
            }

            $workerId = $this->createWorker($request, $updateType, $inputForHash);
            if (!$workerId) {
                return ResultUtil::errorResult('Could not create worker.', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $messageBodyAsArray['worker_id'] = $workerId;
            $this->producer->sendCommand(WorkerAction::UPDATE_ANIMAL_DATA, $messageBodyAsArray);
        }
        catch(\Exception $e) {
            $this->processWorkerError($e, $workerId);
            return ResultUtil::internalServerError();
        }
        return ResultUtil::successResult('OK');
    }

    /**
     * @param Request $request
     * @param int $updateType
     * @param string $inputForHash
     * @return bool
     * @throws \Exception
     */
    private function isSimilarNonExpiredTaskAlreadyInProgress(Request $request, int $updateType, string $inputForHash): bool
    {
        $updateWorkerHash = $this->getUpdateAnimalDataWorkerHash($request, $updateType, $inputForHash);

        return $this->em->getRepository(UpdateAnimalDataWorker::class)->isSimilarNonExpiredTaskAlreadyInProgress($updateWorkerHash);
    }

    /**
     * @return JsonResponse
     */
    private function updateWorkerInProgressAlreadyExistErrorResponse(): JsonResponse
    {
        $message = $this->translator->trans('A SIMILAR TASK IS ALREADY BEING GENERATED');
        return ResultUtil::errorResult($message, Response::HTTP_PRECONDITION_REQUIRED);
    }

    private function createWorker(Request $request, int $updateType, $inputForHash = '') : ?int
    {
        try {
            $updateWorker = new UpdateAnimalDataWorker();
            $updateWorker->setOwner($this->userService->getAccountOwner($request));
            $updateWorker->setActionBy($this->userService->getUser());
            $updateWorker->setLocation($this->userService->getSelectedLocation($request));
            $updateWorker->setUpdateType($updateType);
            $updateWorker->setHash($this->getUpdateAnimalDataWorkerHash($request, $updateType, $inputForHash));

            $this->em->persist($updateWorker);
            $this->em->flush();

            return $updateWorker->getId();
        }
        catch(\Exception $e) {
            $this->logExceptionAsError($e);
        }

        return null;
    }

    /**
     * @param \Exception $e
     * @param int|null $workerId
     */
    private function processWorkerError(\Exception $e, int $workerId = null)
    {
        $this->logExceptionAsError($e);
        if ($workerId) {
            $workerRecord = $this->em->getRepository(UpdateAnimalDataWorker::class)->find($workerId);
            if ($workerRecord) {
                try {
                    $this->em->remove($workerRecord);
                    $this->em->flush();
                } catch (ORMException $ORMException) {
                    $this->logExceptionAsError($ORMException);
                }
            }
        }
    }

    /**
     * @param Request $request
     * @param int $updateType
     * @param string $inputForHash
     * @return string
     * @throws \Exception
     */
    private function getUpdateAnimalDataWorkerHash(Request $request, int $updateType, string $inputForHash): string
    {
        $workerType = WorkerType::UPDATE_ANIMAL_DATA;

        $accountOwner = $this->userService->getAccountOwner($request);
        $user = $this->userService->getUser();
        $location = $this->userService->getSelectedLocation($request);

        $metaDataForHash =
            $workerType.'-'.
            $updateType.'-'.
            $user->getId().'-'.
            ($accountOwner ? $accountOwner->getId() : '0').'-'.
            ($location ? $location->getId() : '0')
        ;

        return hash('sha256', $inputForHash . $metaDataForHash);
    }

    /**
     * @param \Exception $exception
     */
    public function logExceptionAsError($exception)
    {
        $this->logger->error($exception->getMessage());
        $this->logger->error($exception->getTraceAsString());
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    public static function getMaxNonExpiredDate(): \DateTime
    {
        $date = new \DateTime();//now
        $interval = new \DateInterval('P7D');// P[eriod] 1 D[ay]
        $date->sub($interval);
        return $date;
    }
}
