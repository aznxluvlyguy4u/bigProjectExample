<?php


namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\CsvFromSqlResultsWriterService;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ReportBySqlService
 *
 * Reports generated from data retrieved by sql query
 */
class ReportBySqlService
{
    const CSV_MIMETYPE = 'text/csv';

    /** @var ObjectManager|EntityManagerInterface */
    protected $em;
    /** @var Connection */
    protected $conn;
    /** @var CsvFromSqlResultsWriterService */
    private $csvWriter;
    /** @var AWSSimpleStorageService */
    private $storageService;

    public function __construct(EntityManagerInterface $em, CsvFromSqlResultsWriterService $csvWriter,
                                AWSSimpleStorageService $storageService)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->storageService = $storageService;
        $this->csvWriter = $csvWriter;
    }

    /**
     * @param string $sql
     * @param string $filename
     * @param string $reportType used as a subfolder in the S3key
     * @return JsonResponse
     */
    public function createReportBySqlQuery($sql, $filename, $reportType)
    {
        $localFilePath = $this->csvWriter->writeFromQuery($sql, $filename);
        if ($localFilePath === null) {
            return Validator::createJsonResponse("Geen data gevonden voor opgegeven criteria", 428);
        }

        return $this->uploadReportFileToS3($localFilePath, FilesystemUtil::concatDirAndFilename($reportType, $filename));
    }


    /**
     * @param $filePath
     * @param $s3Key
     * @return JsonResponse
     */
    private function uploadReportFileToS3($filePath, $s3Key)
    {
        $url = $this->storageService->uploadFromFilePath(
            $filePath,
            $s3Key,
            self::CSV_MIMETYPE
        );

        $this->csvWriter->removeFile($filePath);
        return new JsonResponse([Constant::RESULT_NAMESPACE => $url], 200);
    }
}