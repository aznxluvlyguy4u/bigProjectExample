<?php

namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Controller\ReportAPIController;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Report\InbreedingCoefficientReportData;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\CsvFromSqlResultsWriterService;
use AppBundle\Service\ExcelService;
use AppBundle\Service\UserService;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\TwigOutputUtil;
use AppBundle\Validation\InbreedingCoefficientInputValidator;
use Doctrine\Common\Persistence\ObjectManager;
use Knp\Snappy\GeneratorInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\HttpFoundation\Request;

class InbreedingCoefficientReportService extends ReportServiceBase
{
    const TITLE = 'inbreeding_coefficient_report';
    const TWIG_FILE = 'Report/inbreeding_coefficient_report.html.twig';

    /** @var InbreedingCoefficientReportData */
    private $reportResults;

    public function __construct(ObjectManager $em, ExcelService $excelService, Logger $logger,
                                AWSSimpleStorageService $storageService, CsvFromSqlResultsWriterService $csvWriter, UserService $userService, TwigEngine $templating, GeneratorInterface $knpGenerator, $cacheDir, $rootDir)
    {
        parent::__construct($em, $excelService, $logger, $storageService, $csvWriter, $userService, $templating,
            $knpGenerator, $cacheDir, $rootDir, self::TITLE, self::TITLE);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getReport(Request $request)
    {
        $client = $this->userService->getAccountOwner($request);
        $isAdmin = $this->userService->getEmployee() !== null;
        $content = RequestUtil::getContentAsArray($request);
        $fileType = $request->query->get(QueryParameter::FILE_TYPE_QUERY);

        $inbreedingCoefficientInputValidator = new InbreedingCoefficientInputValidator($this->em, $content, $client, $isAdmin);
        if(!$inbreedingCoefficientInputValidator->getIsInputValid()) {
            return $inbreedingCoefficientInputValidator->createJsonResponse();
        }

        $this->reportResults = new InbreedingCoefficientReportData($this->em, $content, $client);

        if ($fileType === FileType::CSV) {
            return $this->getCsvReport();
        }

        return $this->getPdfReport();
    }


    /**
     * @return JsonResponse
     */
    private function getPdfReport()
    {
        $reportData = $this->reportResults->getData();
        $reportData[ReportLabel::IMAGES_DIRECTORY] = FilesystemUtil::getImagesDirectory($this->rootDir);

        $html = $this->renderView(self::TWIG_FILE, ['variables' => $reportData]);

        if(ReportAPIController::IS_LOCAL_TESTING) {
            //Save pdf in local cache
            return new JsonResponse([Constant::RESULT_NAMESPACE => $this->saveFileLocally($this->getCacheDirFilename(), $html, TwigOutputUtil::pdfPortraitOptions())], 200);
        }

        $pdfOutput = $this->knpGenerator->getOutputFromHtml($html,TwigOutputUtil::pdfPortraitOptions());

        $url = $this->storageService->uploadPdf($pdfOutput, $this->reportResults->getS3Key());

        return new JsonResponse([Constant::RESULT_NAMESPACE => $url], 200);
    }


    /**
     * @return JsonResponse
     */
    private function getCsvReport()
    {
        return $this->generateFile($this->filename,
            $this->reportResults->getCsvData(),self::TITLE,FileType::CSV,!ReportAPIController::IS_LOCAL_TESTING
        );
    }


}