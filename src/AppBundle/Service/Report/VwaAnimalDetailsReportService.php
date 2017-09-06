<?php


namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Controller\ReportAPIController;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\CsvFromSqlResultsWriterService;
use AppBundle\Service\ExcelService;
use AppBundle\Service\UserService;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\TwigOutputUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Knp\Snappy\GeneratorInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class VwaAnimalDetailsReportService
 * @package AppBundle\Service\Report
 */
class VwaAnimalDetailsReportService extends ReportServiceBase
{
    const TITLE = 'vwa_nsfo_dieroverzicht';
    const TWIG_FILE = 'Report/vwa_animal_details_report.html.twig';

    /** @var array */
    private $data;

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
    public function getAnimalDetailsReport(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);

        $ubns = [];
        if ($content->containsKey(JsonInputConstant::LOCATIONS)) {
            $locations = $content->get(JsonInputConstant::LOCATIONS);
            foreach ($locations as $location) {
                $ubn = ArrayUtil::get(JsonInputConstant::UBN, $location);
                if ($ubn) {
                    $ubns[] = $ubn;
                }
            }
        }

        $animalData = [];
        if ($content->containsKey(JsonInputConstant::ANIMALS)) {
            $animalsData = $content->get(JsonInputConstant::ANIMALS);
            foreach ($animalsData as $ulnData) {
                $ulnCountryCode = ArrayUtil::get(JsonInputConstant::ULN_COUNTRY_CODE, $ulnData);
                $ulnNumber = ArrayUtil::get(JsonInputConstant::ULN_NUMBER, $ulnData);

                //TODO
            }
        }

        //TODO retrieve data

        $this->data = [];
        $this->data[ReportLabel::IMAGES_DIRECTORY] = FilesystemUtil::getImagesDirectory($this->rootDir);

        //TODO generate output

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
        return $this->getPdfReportBase(self::TWIG_FILE, $this->data, true);
    }


    private function getCsvReport()
    {
        $this->extension = FileType::CSV;

        $csvData = $this->data;

        return $this->generateFile($this->filename, $csvData,
            self::TITLE,FileType::CSV,!ReportAPIController::IS_LOCAL_TESTING
        );
    }
}