<?php


namespace AppBundle\Service\Report;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Report\ReportBase;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\CsvFromSqlResultsWriterService as CsvWriter;
use AppBundle\Service\ExcelService;
use AppBundle\Service\UserService;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\GeneratorInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Filesystem\Filesystem;
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
    /** @var CsvWriter */
    protected $csvWriter;
    /** @var UserService */
    protected $userService;
    /** @var EngineInterface */
    protected $templating;
    /** @var GeneratorInterface */
    protected $knpGenerator;
    /** @var Logger */
    protected $logger;
    /** @var Filesystem */
    protected $fs;
    /** @var string */
    protected $cacheDir;
    /** @var string */
    protected $rootDir;

    /** @var string */
    protected $folderPath;
    /** @var string */
    protected $filename;

    /**
     * PedigreeRegisterOverviewReportService constructor.
     * @param ObjectManager|EntityManagerInterface $em
     * @param ExcelService $excelService
     * @param Logger $logger
     * @param AWSSimpleStorageService $storageService
     * @param CsvWriter $csvWriter
     * @param UserService $userService
     * @param EngineInterface $templating
     * @param GeneratorInterface $knpGenerator
     * @param String $folderName
     * @param String $rootDir
     */
    public function __construct(ObjectManager $em, ExcelService $excelService, Logger $logger,
                                AWSSimpleStorageService $storageService, CsvWriter $csvWriter,
                                UserService $userService, EngineInterface $templating,
                                GeneratorInterface $knpGenerator, $cacheDir, $rootDir, $folderName)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->logger = $logger;
        $this->storageService = $storageService;
        $this->csvWriter = $csvWriter;
        $this->userService = $userService;
        $this->templating = $templating;
        $this->knpGenerator = $knpGenerator;
        $this->cacheDir = $cacheDir;
        $this->rootDir = $rootDir;

        $this->excelService = $excelService;
        $this->excelService
            ->setFolderName($folderName)
            ->setCreator(self::CREATOR)
            ->setExcelFileType(self::EXCEL_TYPE)
        ;

        $this->fs = new Filesystem();
    }


    public function getS3Key()
    {
        return 'reports'.$this->excelService->getFolder()
            .$this->excelService->getFilename().'.'.$this->excelService->getExtension();
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
     * @param string $filenameWithoutExtension
     * @param array $data
     * @param string $title
     * @param string $fileExtension
     * @param boolean $uploadToS3
     * @return JsonResponse
     * @throws \Exception
     */
    protected function generateFile($filenameWithoutExtension, $data, $title, $fileExtension, $uploadToS3)
    {
        $recordCount = count($data);
        if($recordCount <= 1) {
            $code = 428;
            $message = "Data is empty";
            return new JsonResponse(['code' => $code, "message" => $message], $code);
        }

        $this->logger->notice('Retrieved '.$recordCount.' records');
        $this->logger->notice('Generate data from sql results ... ');

        //These values are also used for the filename on the S3 bucket
        $this->excelService->setFilename($filenameWithoutExtension);
        $this->excelService->setExtension($fileExtension);
        $this->excelService->setTitle($title);

        switch ($fileExtension) {

            case FileType::XLS:
                $this->excelService->generateFromSqlResults($data);
                $localFilePath = $this->excelService->getFullFilepath();
                break;

            case FileType::CSV:
                $localFilePath = $this->csvWriter->write($data,$filenameWithoutExtension .'.'.$fileExtension);
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

        $this->fs->remove($filePath);
        return new JsonResponse([Constant::RESULT_NAMESPACE => $url], 200);
    }


    /**
     * Returns a rendered view.
     *
     * @param string $view       The view name
     * @param array  $parameters An array of parameters to pass to the view
     *
     * @return string The rendered view
     */
    protected function renderView($view, array $parameters = array())
    {
        return $this->templating->render($view, $parameters);
    }


    /**
     * @param string $generatedPdfPath
     * @param $html
     * @param array $pdfOptions
     * @return string
     */
    protected function saveFileLocally($generatedPdfPath, $html, $pdfOptions = null)
    {
        $this->knpGenerator->generateFromHtml($html, $generatedPdfPath, $pdfOptions);
        return $generatedPdfPath;
    }

}