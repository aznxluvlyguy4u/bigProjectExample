<?php


namespace AppBundle\Service\Report;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Controller\ReportAPIController;
use AppBundle\Entity\Client;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Report\ReportBase;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\CsvFromSqlResultsWriterService as CsvWriter;
use AppBundle\Service\ExcelService;
use AppBundle\Service\UserService;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\TwigOutputUtil;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\GeneratorInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\DataCollectorTranslator;

/**
 * Class ReportServiceBase
 * @package AppBundle\Service\Report
 */
class ReportServiceBase
{
    const EXCEL_TYPE = 'Excel2007';
    const CREATOR = 'NSFO';
    const DEFAULT_EXTENSION = FileType::PDF;
    const DEFAULT_FILENAME = 'NFSO_Report';

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
    /** @var TwigEngine */
    protected $templating;
    /** @var DataCollectorTranslator */
    protected $translator;
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
    /** @var string */
    protected $folderName;
    /** @var string */
    protected $extension;

    /** @var array */
    protected $inputErrors;

    /** @var array */
    protected $convertedResult;

    /**
     * PedigreeRegisterOverviewReportService constructor.
     * @param ObjectManager|EntityManagerInterface $em
     * @param ExcelService $excelService
     * @param Logger $logger
     * @param AWSSimpleStorageService $storageService
     * @param CsvWriter $csvWriter
     * @param UserService $userService
     * @param TwigEngine $templating
     * @param DataCollectorTranslator $translator
     * @param GeneratorInterface $knpGenerator
     * @param String $folderName
     * @param String $rootDir
     * @param String $filename
     */
    public function __construct(ObjectManager $em, ExcelService $excelService, Logger $logger,
                                AWSSimpleStorageService $storageService, CsvWriter $csvWriter,
                                UserService $userService, TwigEngine $templating,
                                DataCollectorTranslator $translator,
                                GeneratorInterface $knpGenerator, $cacheDir, $rootDir, $folderName, $filename = self::DEFAULT_FILENAME)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->logger = $logger;
        $this->storageService = $storageService;
        $this->csvWriter = $csvWriter;
        $this->userService = $userService;
        $this->templating = $templating;
        $this->translator = $translator;
        $this->knpGenerator = $knpGenerator;
        $this->cacheDir = $cacheDir;
        $this->rootDir = $rootDir;

        $this->excelService = $excelService;
        $this->excelService
            ->setFolderName($folderName)
            ->setCreator(self::CREATOR)
            ->setExcelFileType(self::EXCEL_TYPE)
        ;

        $this->extension = self::DEFAULT_EXTENSION;
        $this->folderPath = $folderName;
        $this->filename = $filename;

        $this->fs = new Filesystem();
        $this->inputErrors = [];
    }


    public function getS3Key()
    {
        $path = FilesystemUtil::concatDirAndFilename('reports', $this->folderName);
        return FilesystemUtil::concatDirAndFilename($path, $this->getFilename());
    }


    public function getContentType()
    {
        return $this->excelService->getContentMimeType();
    }


    public function getCacheSubFolder()
    {
        return FilesystemUtil::concatDirAndFilename($this->cacheDir, $this->folderName);
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
        $this->filename = $filenameWithoutExtension;
        $this->extension = $fileExtension;

        $this->excelService->setFilename($this->filename);
        $this->excelService->setExtension($this->extension);
        $this->excelService->setTitle($title);

        switch ($fileExtension) {

            case FileType::XLS:
                $this->excelService->generateFromSqlResults($data);
                $localFilePath = $this->excelService->getFullFilepath();
                break;

            case FileType::CSV:
                $localFilePath = $this->csvWriter->write($data,$this->getFilename());
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
     * @param string $twigFile
     * @param array|object $data
     * @param boolean $isLandscape
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function getPdfReportBase($twigFile, $data, $isLandscape = true)
    {
        $html = $this->renderView($twigFile, ['variables' => $data]);

        if (ReportAPIController::DISPLAY_PDF_AS_HTML) {
            $response = new Response($html);
            $response->headers->set('Content-Type', 'text/html');
            return $response;
        }

        $this->extension = FileType::PDF;

        $pdfOptions = $isLandscape ? TwigOutputUtil::pdfLandscapeOptions() : TwigOutputUtil::pdfPortraitOptions();

        if(ReportAPIController::IS_LOCAL_TESTING) {
            //Save pdf in local cache
            return ResultUtil::successResult($this->saveFileLocally($this->getCacheDirFilename(), $html, $pdfOptions));
        }

        $pdfOutput = $this->knpGenerator->getOutputFromHtml($html, $pdfOptions);

        $url = $this->storageService->uploadPdf($pdfOutput, $this->getS3Key());

        return ResultUtil::successResult($url);
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


    /**
     * @param array $arraySets
     * @param string $keyPrefix
     * @param array $keysToIgnore
     * @return array
     */
    protected function convertNestedArraySetsToSqlResultFormat(array $arraySets, $keysToIgnore = [], $keyPrefix = '')
    {
        $result = [];
        foreach ($arraySets as $arraySet) {
            $result[] = $this->convertNestedArrayToSqlResultFormat($arraySet, $keysToIgnore, $keyPrefix);
        }
        $this->purgeConvertedResult();

        return $result;
    }


    /**
     * @param array $data
     * @param array $keysToIgnore
     * @return mixed
     */
    protected function unsetNestedKeys(array $data, array $keysToIgnore = [])
    {
        $rows = array_keys($data);
        foreach ($rows as $row) {
            foreach ($keysToIgnore as $keyToIgnore)
            {
                unset($data[$row][$keyToIgnore]);
            }
        }
        return $data;
    }



    /**
     * @param array $array
     * @param string $keyPrefix
     * @param array $keysToIgnore
     * @param string $semiColonReplacement
     * @param boolean $purgeResult
     * @return array
     * @throws \Exception
     */
    protected function convertNestedArrayToSqlResultFormat(array $array, $keysToIgnore = [], $keyPrefix = '', $semiColonReplacement = ',', $purgeResult = true)
    {
        $keySeparator = '_';

        if ($purgeResult) { $this->purgeConvertedResult(); }

        foreach ($array as $key => $item) {

            if (in_array($key, $keysToIgnore)) {
                continue;
            }

            $keyForResult = $keyPrefix !== '' ? $keyPrefix.$keySeparator.$key : $key;

            if (is_array($item)) {
                self::convertNestedArrayToSqlResultFormat($item, $keysToIgnore, $keyForResult, $semiColonReplacement,false);

            } elseif (is_string($item)) {

                if (key_exists($keyForResult, $this->convertedResult)) {
                    throw new \Exception('Duplicate key: '.$keyForResult);
                }
                $this->convertedResult[$keyForResult] = str_replace(';', $semiColonReplacement, $item);
            }
        }

        return $this->convertedResult;
    }



    protected function purgeConvertedResult()
    {
        $this->convertedResult = [];
    }


    /**
     * @return string
     */
    protected function getCacheDirFilename()
    {
        $path = FilesystemUtil::concatDirAndFilename($this->cacheDir, $this->folderName);
        return FilesystemUtil::concatDirAndFilename($path, $this->getFilename());
    }


    /**
     * @return string
     */
    protected function getFilename()
    {
        return $this->getFilenameWithoutExtension().'.'.$this->extension;
    }


    protected function getFilenameWithoutExtension()
    {
        return $this->filename.'_'.TimeUtil::getTimeStampNowForFiles();
    }


    /**
     * @return Client|\AppBundle\Entity\Employee|\AppBundle\Entity\Person
     */
    protected function getUser()
    {
        return $this->userService->getUser();
    }


}