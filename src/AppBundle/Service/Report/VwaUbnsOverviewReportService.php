<?php


namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Controller\ReportAPIController;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\Locale;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Service\AWSSimpleStorageService;
use AppBundle\Service\CsvFromSqlResultsWriterService;
use AppBundle\Service\ExcelService;
use AppBundle\Service\UserService;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\FilesystemUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Knp\Snappy\GeneratorInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class VwaUbnsOverviewReportService
 * @package AppBundle\Service\Report
 */
class VwaUbnsOverviewReportService extends ReportServiceBase
{
    const TITLE = 'nsfo third party ubn overview';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const TWIG_FILE = 'Report/vwa_ubns_overview_report.html.twig';

    const BLANK_STATUS = '';

    const ERROR_UBNS_NOT_FOUND = 'Er bestaan geen actieve locaties voor de opgegeven ubns: ';

    /** @var array */
    private $data;


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getUbnsOverviewReport(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);

        $ubns = [];
        $incorrectUbns = [];
        if ($content->containsKey(JsonInputConstant::LOCATIONS)) {
            $locations = $content->get(JsonInputConstant::LOCATIONS);
            foreach ($locations as $location) {
                $ubn = ArrayUtil::get(JsonInputConstant::UBN, $location);
                if ($ubn === null) {
                    continue;
                }

                $location = $this->em->getRepository(Location::class)->findOneByActiveUbn($ubn);

                if ($location) {
                    $ubns[$ubn] = $location;
                } else {
                    $incorrectUbns[] = $ubn;
                }
            }
        }

        if (count($incorrectUbns) > 0) {
            return ResultUtil::errorResult(self::ERROR_UBNS_NOT_FOUND . implode(', ', $incorrectUbns),Response::HTTP_BAD_REQUEST);
        }

        $this->filename = $this->translate(self::FILENAME);
        $this->folderName = $this->translate(self::FOLDER_NAME);

        $locationHealthData = [];
        /** @var Location $location */
        foreach ($ubns as $ubn => $location) {
            $locationHealth = $location->getLocationHealth();

            $caseousLymphadenitisStatus = self::BLANK_STATUS;
            $maediVisnaStatus = self::BLANK_STATUS;
            $scrapieStatus = self::BLANK_STATUS;
            $caeStatus = self::BLANK_STATUS; //Only for goats. Currently not supported.
            if ($locationHealth) {
                $caseousLymphadenitisStatus = $this->editStatus($locationHealth->getCurrentCaseousLymphadenitisStatus());
                $maediVisnaStatus = $this->editStatus($locationHealth->getCurrentCaseousLymphadenitisStatus());
                $scrapieStatus = $this->editStatus($locationHealth->getCurrentScrapieStatus());
                $caeStatus = self::BLANK_STATUS; //Only for goats. Currently not supported.
            }

            $locationHealthData[$ubn] = [
                ReportLabel::UBN => $ubn,
                ReportLabel::CASEOUS_LYMPHADENITIS_STATUS => $caseousLymphadenitisStatus,
                ReportLabel::MAEDI_VISNA_STATUS => $maediVisnaStatus,
                ReportLabel::SCRAPIE_STATUS => $scrapieStatus,
                ReportLabel::CAE_STATUS => $caeStatus,
            ];
        }

        ksort($locationHealthData);

        $this->data[ReportLabel::LOCATIONS] = $locationHealthData;
        $this->data[ReportLabel::IMAGES_DIRECTORY] = FilesystemUtil::getImagesDirectory($this->rootDir);
        $this->data[ReportLabel::NAME] = $this->getUser()->getFullName();

        $fileType = $request->query->get(QueryParameter::FILE_TYPE_QUERY);
        $fileType = $fileType !== FileType::CSV ? FileType::PDF : $fileType;

        ksort($ubns);
        $log = ActionLogWriter::getVwaUbnsOverviewReport($this->em, $this->getUser(), array_keys($ubns), $fileType);
        if ($log instanceof JsonResponse) {
            return $log;
        }

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
        return $this->getPdfReportBase(self::TWIG_FILE, $this->data, false);
    }


    private function getCsvReport()
    {
        $this->extension = FileType::CSV;

        $csvData = $this->data[ReportLabel::LOCATIONS];

        return $this->generateFile($this->filename, $csvData,
            self::TITLE,FileType::CSV,!$this->outputReportsToCacheFolderForLocalTesting
        );
    }


    /**
     * @param string $status
     * @return string
     */
    private function editStatus($status)
    {
        if ($status === null) {
            return self::BLANK_STATUS;
        }
        return ucfirst(mb_strtolower($this->translator->trans($status)));
    }
}