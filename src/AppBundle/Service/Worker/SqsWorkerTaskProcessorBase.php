<?php


namespace AppBundle\Service\Worker;

use AppBundle\Enumerator\SqsCommandType;
use AppBundle\Service\BaseSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;

abstract class SqsWorkerTaskProcessorBase
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Logger */
    private $logger;
    /** @var BaseSerializer */
    private $serializer;

    public function __construct(EntityManagerInterface $em,
                                BaseSerializer $serializer,
                                Logger $logger)
    {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getManager(): EntityManagerInterface
    {
        return $this->em;
    }

    /**
     * @return BaseSerializer
     */
    protected function getSerializer(): BaseSerializer
    {
        return $this->serializer;
    }

    /**
     * @return Logger
     */
    protected function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * @param $sqsCommandTypeConstantValue
     */
    protected function logStartMessage($sqsCommandTypeConstantValue): void
    {
        $this->logger->debug('... processing '. SqsCommandType::getName($sqsCommandTypeConstantValue).' ...');
    }
}