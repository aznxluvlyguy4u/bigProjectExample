<?php


namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Controller\ReportAPIController;
use AppBundle\Entity\Animal;
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
use Doctrine\Common\Persistence\ObjectManager;
use Knp\Snappy\GeneratorInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class VwaAnimalDetailsReportService
 * @package AppBundle\Service\Report
 */
class VwaAnimalDetailsReportService extends ReportServiceBase
{
    const TITLE = 'nsfo third party animal overview';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const TWIG_FILE = 'Report/vwa_animal_details_report.html.twig';

    /** @var array */
    private $data;


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

        $ulns = [];
        if ($content->containsKey(JsonInputConstant::ANIMALS)) {
            $ulns = $content->get(JsonInputConstant::ANIMALS);
        }

        $animals = $this->em->getRepository(Animal::class)->findByUbnsOrUlns($ubns, $ulns);

        $this->data[ReportLabel::ANIMALS] = $animals;
        $this->data[ReportLabel::IMAGES_DIRECTORY] = FilesystemUtil::getImagesDirectory($this->rootDir);
        $this->data[ReportLabel::NAME] = $this->getUser()->getFullName();

        $fileType = $request->query->get(QueryParameter::FILE_TYPE_QUERY);
        $fileType = $fileType !== FileType::CSV ? FileType::PDF : $fileType;

        $log = ActionLogWriter::getVwaAnimalDetailsReport($this->em, $this->getUser(), $ubns, $ulns, $fileType);
        if ($log instanceof JsonResponse) {
            return $log;
        }

        $this->filename = $this->translate(self::FILENAME);
        $this->folderName = $this->translate(self::FOLDER_NAME);

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
        throw new \Exception('Not implemented', Response::HTTP_NOT_IMPLEMENTED);

        $this->extension = FileType::CSV;

        $csvData = $this->data;

        return $this->generateFile($this->filename, $csvData,
            self::TITLE,FileType::CSV,!$this->outputReportsToCacheFolderForLocalTesting
        );
    }
}