<?php


namespace AppBundle\Service;


use AppBundle\Enumerator\MixBlupType;
use AppBundle\Setting\MixBlupFolder;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class MixBlupOutputFilesService
 * @package AppBundle\Service
 */
class MixBlupOutputFilesService implements MixBlupServiceInterface
{
    /** @var Connection */
    private $conn;

    /** @var ObjectManager */
    private $em;

    /** @var AWSSimpleStorageService */
    private $s3Service;

    /** @var MixBlupOutputQueueService */
    private $queueService;

    /** @var string */
    private $currentEnvironment;

    /** @var string */
    private $cacheDir;

    /** @var string */
    private $workingFolder;

    /** @var array */
    private $mixBlupProcesses;

    /** @var Logger */
    private $logger;

    /**
     * MixBlupOutputFilesService constructor.
     * @param ObjectManager $em
     * @param AWSSimpleStorageService $s3Service
     * @param MixBlupOutputQueueService $queueService
     * @param string $currentEnvironment
     * @param string $cacheDir
     * @param Logger $logger
     */
    public function __construct(ObjectManager $em, AWSSimpleStorageService $s3Service, MixBlupOutputQueueService $queueService,
                                $currentEnvironment, $cacheDir, $logger = null)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->s3Service = $s3Service;
        $this->queueService = $queueService;
        $this->currentEnvironment = $currentEnvironment;
        $this->cacheDir = $cacheDir;
        $this->logger = $logger;
        $this->workingFolder = $cacheDir.'/'.MixBlupFolder::ROOT;

        $this->mixBlupProcesses = [];
//        $this->mixBlupProcesses[MixBlupType::EXTERIOR] = new ExteriorProcess($em, $this->workingFolder);
//        $this->mixBlupProcesses[MixBlupType::LAMB_MEAT_INDEX] = new LambMeatIndexProcess($em, $this->workingFolder);
//        $this->mixBlupProcesses[MixBlupType::FERTILITY] = new ReproductionProcess($em, $this->workingFolder);
    }


    public function run()
    {
        // TODO: Implement run() method.
    }
}