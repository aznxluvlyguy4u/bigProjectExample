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
    /** @var FeedbackJsonMessageReaderInterface */
    private $jsonMessageReader;

    public function __construct(EntityManagerInterface $em,
                                FeedbackJsonMessageReaderInterface $jsonMessageReader,
                                Logger $logger)
    {
        $this->em = $em;
        $this->jsonMessageReader = $jsonMessageReader;
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
     * @return FeedbackJsonMessageReaderInterface
     */
    protected function getJsonMessageReader(): FeedbackJsonMessageReaderInterface
    {
        return $this->jsonMessageReader;
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