<?php

namespace AppBundle\Command;

use AppBundle\Service\AwsQueueServiceBase;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoInfoCommand extends ContainerAwareCommand
{
    const TITLE = 'NSFO SYSTEM SETTINGS';

    /** @var ObjectManager $em */
    private $em;
    /** @var OutputInterface */
    private $output;

    protected function configure()
    {
        $this
            ->setName('nsfo:info')
            ->setDescription(self::TITLE)
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $this->output = $output;

        //Print intro
        $this->writeln(CommandUtil::generateTitle(self::TITLE));

        $this->writeln(['___ ENVIRONMENT ___',
            'kernel.environment: ' . $this->getParameter('kernel.environment'),
            'parameters.yml, environment: ' . $this->getParameter('environment'),
            '']);

        $this->writeln(['___ FOLDERS ___',
            'rootDir: '.$this->getParameter('kernel.root_dir'),
            'cacheDir: '.$this->getParameter('kernel.cache_dir'),
            '']);

        $this->writeln(['___ DATABASE ___',DoctrineUtil::getDatabaseHostAndNameString($em),
            'RedisHost: ' . $this->getParameter('redis_host'),'']);

        $this->printQueues();
        $this->printStorageService();
    }


    protected function printQueues()
    {
        $this->writeln('___ SQS MESSAGE QUEUES ___');

        $externalQueueService = $this->getContainer()->get('app.aws.queueservice.external');
        $internalQueueService = $this->getContainer()->get('app.aws.queueservice.internal');
        $mixblupInputQueueService = $this->getContainer()->get('app.aws.queueservice.mixblup_input');
        $mixblupOutputQueueService = $this->getContainer()->get('app.aws.queueservice.mixblup_output');

        $queueServices = [
            'External worker queue' => $externalQueueService,
            'Internal worker queue' => $internalQueueService,
            'MiXBLUP input queue' => $mixblupInputQueueService,
            'MiXBLUP output queue' => $mixblupOutputQueueService,
        ];

        /**
         * @var string $queueName
         * @var AwsQueueServiceBase $queueService
         */
        foreach ($queueServices as $queueName => $queueService)
        {
            if($queueService === null) {
                dump($queueName);die;
            }
            $this->writeln($queueName . ', queueId: ' . $queueService->getQueueId());
        }
        $this->writeln('');

        $queueServicePrefixes = [
            'aws_sqs_queue_exteral_prefix',
            'aws_sqs_queue_interal_prefix',
            'aws_sqs_queue_mixblup_input_prefix',
            'aws_sqs_queue_mixblup_output_prefix',
        ];

        $this->writeln('___ SQS MESSAGE QUEUES PREFIXES ___');

        foreach ($queueServicePrefixes as $queueServicePrefix) {
            $this->writeln($queueServicePrefix . ': '.$this->getParameter($queueServicePrefix));
        }
        $this->writeln('');
    }


    protected function printStorageService()
    {
        $this->writeln('___ S3 STORAGE ___');
        /** @var AWSSimpleStorageService $storageService */
        $storageService = $this->getContainer()->get('app.aws.storageservice');
        $this->writeln('Bucket: ' . $storageService->getBucket());
        $this->writeln('PathPrefix: ' . $storageService->getPathApppendage());
    }


    /**
     * @param $line
     */
    protected function writeln($line)
    {
        $this->output->writeln($line);
    }


    protected function getParameter($parameter)
    {
        return $this->getContainer()->getParameter($parameter);
    }
}
