<?php


namespace AppBundle\Processor\Rvo;


use AppBundle\Enumerator\RequestType;
use AppBundle\Service\AwsRawInternalSqsService;
use AppBundle\Worker\Logic\DeclareAnimalFlagAction;
use Psr\Log\LoggerInterface;

class RawInternalWorker
{
    /** @var AwsRawInternalSqsService */
    private $rawInternalQueueService;

    /** @var LoggerInterface */
    private $logger;

    /** @var DeclareAnimalFlagAction  */
    private $declareAnimalFlagAction;

    public function __construct(
        AwsRawInternalSqsService $rawInternalQueueService,
        LoggerInterface $logger
    )
    {
        $this->rawInternalQueueService = $rawInternalQueueService;
        $this->logger = $logger;
    }

    /**
     * @required
     *
     * @param DeclareAnimalFlagAction $service
     */
    public function setDeclareAnimalFlagAction(DeclareAnimalFlagAction $service)
    {
        $this->declareAnimalFlagAction = $service;
    }

    public function run()
    {
        // TODO get queue message
        $rvoXmlResponseContent = 'get from queue';
        $requestType = 'get from message taskType';

        // TODO add switch logic and catch exceptions

        // TODO Don't loop to prevent memory leaks

        switch ($requestType) {
            case RequestType::DECLARE_ANIMAL_FLAG:
                $this->declareAnimalFlagAction->process($rvoXmlResponseContent);
                break;
            default:
                $errorMessage = 'Unsupported request type for internal worker '.$requestType;
                $this->logger->emergency($errorMessage);
                throw new \Exception($errorMessage);
        }
    }
}