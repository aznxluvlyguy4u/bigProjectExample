<?php


namespace AppBundle\Service;


use AppBundle\Enumerator\QueueType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class InfoService
 */
class InfoService
{

    /** @var EntityManagerInterface */
    private $em;
    /** @var Logger */
    private $logger;
    /** @var AWSSimpleStorageService */
    private $storageService;

    /** @var string */
    private $systemEnvironment;
    /** @var string */
    private $parameterEnvironment;
    /** @var string */
    private $rootDir;
    /** @var string */
    private $cacheDir;
    /** @var string */
    private $redisHost;
    /** @var array */
    private $queues;

    public function __construct(EntityManagerInterface $em, Logger $logger,                                              AWSSimpleStorageService $storageService,
                                $systemEnvironment, $parameterEnvironment,
                                $rootDir, $cacheDir, $redisHost,
                                AwsExternalQueueService $externalQueue,
                                AwsInternalQueueService $internalQueue,
                                MixBlupInputQueueService $mixBlupInputQueue,
                                MixBlupOutputQueueService $mixBlupOutputQueue
    )
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->storageService = $storageService;

        $this->systemEnvironment = $systemEnvironment;
        $this->parameterEnvironment = $parameterEnvironment;
        $this->rootDir = $rootDir;
        $this->cacheDir = $cacheDir;
        $this->redisHost = $redisHost;

        $this->queues = [
            QueueType::EXTERNAL => $externalQueue,
            QueueType::INTERNAL => $internalQueue,
            QueueType::MIXBLUP_INPUT => $mixBlupInputQueue,
            QueueType::MIXBLUP_OUTPUT => $mixBlupOutputQueue,
        ];

    }


    public function printInfo()
    {
        //Print intro
        $this->writeln(CommandUtil::generateTitle(CliOptionsService::INFO_SYSTEM_SETTINGS));

        $this->writeln(['___ ENVIRONMENT ___',
            'kernel.environment: ' . $this->systemEnvironment,
            'parameters.yml, environment: ' . $this->parameterEnvironment,
            '']);

        $this->writeln(['___ FOLDERS ___',
            'rootDir: '.$this->rootDir,
            'cacheDir: '.$this->cacheDir,
            '']);

        $this->writeln(['___ DATABASE ___',DoctrineUtil::getDatabaseHostAndNameString($this->em),
            'RedisHost: ' . $this->redisHost,'']);

        $this->printQueues();
        $this->printStorageService();
    }


    protected function printQueues()
    {
        $this->writeln('___ SQS MESSAGE QUEUES ___');

        /**
         * @var string $queueName
         * @var AwsQueueServiceBase $queueService
         */
        foreach ($this->queues as $queueName => $queueService)
        {
            $this->writeln($queueName . ', queueId: ' . $queueService->getQueueId());
        }
        $this->writeln('');
    }


    protected function printStorageService()
    {
        $this->writeln('___ S3 STORAGE ___');
        $this->writeln('Bucket: ' . $this->storageService->getBucket());
        $this->writeln('PathPrefix: ' . $this->storageService->getPathApppendage());
    }


    /**
     * @param $input
     */
    protected function writeln($input)
    {
        if (is_array($input)) {
            foreach ($input as $line) { $this->writeln($line); }
        } else {
            $this->logger->notice($input);
        }
    }

}