<?php


namespace AppBundle\Service\Report;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\ExcelService;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ReportServiceBase
 * @package AppBundle\Service\Report
 */
class ReportServiceBase
{
    const EXCEL_TYPE = 'Excel2007';
    const CREATOR = 'NSFO';

    /** @var ObjectManager|EntityManagerInterface */
    protected $em;
    /** @var Connection */
    protected $conn;
    /** @var ExcelService */
    protected $excelService;
    /** @var AWSSimpleStorageService */
    protected $storageService;
    /** @var Logger */
    protected $logger;

    /**
     * PedigreeRegisterOverviewReportService constructor.
     * @param ObjectManager|EntityManagerInterface $em
     * @param ExcelService $excelService
     * @param Logger $logger
     * @param AWSSimpleStorageService $storageService
     * @param String $folderName
     */
    public function __construct(ObjectManager $em, ExcelService $excelService, Logger $logger,
                                AWSSimpleStorageService $storageService, $folderName)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->logger = $logger;
        $this->storageService = $storageService;

        $this->excelService = $excelService;
        $this->excelService
            ->setFolderName($folderName)
            ->setCreator(self::CREATOR)
            ->setExcelFileType(self::EXCEL_TYPE)
        ;
    }


    public function getS3Key()
    {
        return 'reports'.$this->excelService->getFolder().$this->excelService->getFilename();
    }


    public function getContentType()
    {
        return $this->excelService->getContentMimeType();
    }


    public function getCacheSubFolder()
    {
        return $this->excelService->getCacheSubFolder();
    }


    /**
     * @param string $filename
     * @param array $data
     * @param string $title
     * @param string $fileType
     * @param boolean $uploadToS3
     * @return JsonResponse
     * @throws \Exception
     */
    protected function generateFile($filename, $data, $title, $fileType, $uploadToS3)
    {
        $recordCount = count($data);
        if($recordCount <= 1) {
            $code = 428;
            $message = "Data is empty";
            return new JsonResponse(['code' => $code, "message" => $message], $code);
        }

        $this->logger->notice('Retrieved '.$recordCount.' records');
        $this->logger->notice('Generate data from sql results ... ');

        $this->excelService->setFilename($filename);
        $this->excelService->setTitle($title);


        switch ($fileType) {

            case FileType::XLS:
                $this->excelService->generateFromSqlResults($data);
                $localFilePath = $this->excelService->getFullFilepathWithExtension();
                break;

            case FileType::CSV:
                //TODO
                throw new \Exception('CSV File generation still to be implemented');
                break;

            default:
                throw new \Exception('Incorrect FileType given');
        }

        if($localFilePath instanceof JsonResponse) { return $localFilePath; }

        if ($uploadToS3) {
            return $this->uploadReportFileToS3($localFilePath);
        }

        return new JsonResponse([Constant::RESULT_NAMESPACE => $localFilePath], 200);
    }


    /**
     * @param string $filePath
     * @return JsonResponse
     */
    protected function uploadReportFileToS3($filePath)
    {
        $s3Service = $this->storageService;
        $url = $s3Service->uploadFromFilePath(
            $filePath,
            $this->getS3Key(),
            $this->getContentType()
        );

        FilesystemUtil::purgeFolder($this->getCacheSubFolder());
        return new JsonResponse([Constant::RESULT_NAMESPACE => $url], 200);
    }


}