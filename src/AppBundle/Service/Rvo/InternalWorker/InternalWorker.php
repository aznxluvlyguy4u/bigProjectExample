<?php


namespace AppBundle\Service\Rvo\InternalWorker;


use AppBundle\Enumerator\RequestType;
use AppBundle\Worker\Logic\DeclareAnimalFlagAction;
use Psr\Log\LoggerInterface;

class InternalWorker
{
    /** @var LoggerInterface */
    private $logger;

    /** @var DeclareAnimalFlagAction  */
    private $declareAnimalFlagAction;

    /**
     * @required
     *
     * @param DeclareAnimalFlagAction $service
     */
    public function setDeclareAnimalFlagAction(DeclareAnimalFlagAction $service)
    {
        $this->declareAnimalFlagAction = $service;
    }

    /**
     * @required
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
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