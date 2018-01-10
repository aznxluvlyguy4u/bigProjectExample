<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\QueueDashboardAPIControllerInterface;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class QueueDashboardService extends ControllerServiceBase implements QueueDashboardAPIControllerInterface
{
    const EXTERNAL = 'external';
    const EXTERNAL_ERROR = 'external_error';
    const INTERNAL = 'internal';
    const INTERNAL_ERROR = 'internal_error';

    /** @var AwsExternalQueueService */
    private $externalQueueService;
    /** @var AwsInternalQueueService */
    private $internalQueueService;

    /**
     * @required
     *
     * @param AwsExternalQueueService $externalQueueService
     */
    public function setExternalQueueService(AwsExternalQueueService $externalQueueService)
    {
        $this->externalQueueService = $externalQueueService;
    }

    /**
     * @required
     *
     * @param AwsInternalQueueService $internalQueueService
     */
    public function setInternalQueueService(AwsInternalQueueService $internalQueueService)
    {
        $this->internalQueueService = $internalQueueService;
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getQueueSizes(Request $request)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        return ResultUtil::successResult([
            self::EXTERNAL => $this->externalQueueService->getSizeOfQueue(),
            self::EXTERNAL_ERROR => $this->externalQueueService->getSizeOfErrorQueue(),
            self::INTERNAL => $this->internalQueueService->getSizeOfQueue(),
            self::INTERNAL_ERROR => $this->internalQueueService->getSizeOfErrorQueue(),
        ]);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    function moveErrorQueueMessages(Request $request)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::SUPER_ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        return ResultUtil::successResult([
            self::EXTERNAL => $this->externalQueueService->moveErrorQueueMessagesToPrimaryQueue(),
            self::INTERNAL => $this->internalQueueService->moveErrorQueueMessagesToPrimaryQueue(),
        ]);
    }


}