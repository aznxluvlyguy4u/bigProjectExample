<?php


namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Controller\ReportAPIController;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Report\PedigreeCertificates;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\CsvFromSqlResultsWriterService;
use AppBundle\Service\ExcelService;
use AppBundle\Service\UserService;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\TwigOutputUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\UlnValidator;
use Doctrine\Common\Persistence\ObjectManager;
use Knp\Snappy\GeneratorInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\HttpFoundation\Request;

class PedigreeCertificateReportService extends ReportServiceBase
{
    const TITLE = 'pedigree_certificates_report';
    const TWIG_FILE = 'Report/pedigree_certificates.html.twig';
    const TWIG_FILE_BETA = 'Report/pedigree_certificates_beta.html.twig';

    /** @var PedigreeCertificates */
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
        $client = null;
        $location = null;

        if(!AdminValidator::isAdmin($this->userService->getEmployee(), AccessLevelType::ADMIN)) {
            $client = $this->userService->getAccountOwner($request);
            $location = $this->userService->getSelectedLocation($request);
        }
        $content = RequestUtil::getContentAsArray($request);

        //Validate if given ULNs are correct AND there should at least be one ULN given
        $ulnValidator = new UlnValidator($this->em, $content, true, null, $location);
        if(!$ulnValidator->getIsUlnSetValid()) {
            return $ulnValidator->createArrivalJsonErrorResponse();
        }

        $this->reportResults = new PedigreeCertificates($this->em, $content, $client, $location);

        $fileType = $request->query->get(QueryParameter::FILE_TYPE_QUERY);

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
        //Or use... $this->getCurrentEnvironment() == Environment::PROD;
        if(ReportAPIController::IS_USE_PROD_VERSION_OUTPUT) {
            $twigFile = self::TWIG_FILE;
        } else {
            //containing extra unfinished features
            $twigFile = self::TWIG_FILE_BETA;
        }

        $variables = $this->reportResults->getReports();
        $html = $this->renderView($twigFile, ['variables' => $variables]);

        if(ReportAPIController::IS_LOCAL_TESTING) {
            //Save pdf in local cache
            return new JsonResponse([Constant::RESULT_NAMESPACE => $this->saveFileLocally($this->reportResults->getFilePath($this->cacheDir), $html, TwigOutputUtil::pdfLandscapeOptions())], 200);
        }

        $pdfOutput = $this->knpGenerator->getOutputFromHtml($html,TwigOutputUtil::pdfLandscapeOptions());

        $url = $this->storageService->uploadPdf($pdfOutput, $this->reportResults->getS3Key());

        return new JsonResponse([Constant::RESULT_NAMESPACE => $url], 200);
    }


    /**
     * @return JsonResponse
     */
    private function getCsvReport()
    {
        $keysToIgnore = [
            'breederIndexStars',
            'mBreederIndexStars',
            'fBreederIndexStars',
            'extIndexStars',
            'vlIndexStars',
            'breederIndexNoAcc',
            'mBreederIndexNoAcc',
            'fBreederIndexNoAcc',
            'extIndexNoAcc',
            //ignore the following two keys, so the columns always match in the csv
            'litterSize',
            'litterCount',
        ];
        $csvData = $this->convertNestedArraySetsToSqlResultFormat($this->reportResults->getReports(), $keysToIgnore);

        return $this->generateFile($this->filename,
            $csvData,self::TITLE,FileType::CSV,!ReportAPIController::IS_LOCAL_TESTING
        );
    }


}