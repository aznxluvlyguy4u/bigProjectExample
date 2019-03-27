<?php
namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Option\CompanyRegisterReportOptions;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\Locale;
use AppBundle\Report\InbreedingCoefficientReportData;
use AppBundle\Util\NullChecker;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class CompanyRegisterReportService extends ReportServiceBase
{
    const TITLE = 'company_register_report';
    const TWIG_FILE = 'Report/company_register_report.html.twig';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    const FILE_NAME_REPORT_TYPE = 'COMPANY_REGISTER';

    const MAX_MATE_AGE_IN_MONTHS = 6;

    /**
     * @var Client
     */
    private $client;

    /**
     * @param Person $person
     * @param Location $location
     * @param CompanyRegisterReportOptions $options
     * @return JsonResponse
     * @throws \Exception
     */
    public function getReport(Person $person, Location $location, CompanyRegisterReportOptions $options)
    {
        $this->filename = $this->trans(self::FILE_NAME_REPORT_TYPE).'_'.$location->getUbn();
        $this->folderName = self::FOLDER_NAME;
        $this->extension = $options->getFileType();

        if ($options->getFileType() === FileType::CSV) {
            return $this->generateCsvFileBySqlQuery(
                $this->getFilename(),
                $this->getSqlQuery($options->getSampleDate(), $location->getId()),
                []
            );
        }

        return $this->getPdfReport($person, $location, $options);

//        try {
//
//            $this->setFileNameValues();
//
//            return $this->generateCsvFileBySqlQuery(
//                $this->getFilename(),
//                $this->getQuery(),
//                $this->getBooleanColumns()
//            );
//
//        } catch (\Exception $exception) {
//            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
//        }
    }

    /**
     * @return JsonResponse
     */
    private function getPdfReport(Person $person, Location $location, CompanyRegisterReportOptions $options)
    {
        $reportData = [];
        $reportData['records'] = $this->conn->query(self::getSqlQuery($options->getSampleDate(), $location->getId()))->fetchAll();
        $reportData['summary'] = $this->conn->query(self::getSqlQuery($options->getSampleDate(), $location->getId()))->fetchAll();
        $reportData[ReportLabel::IMAGES_DIRECTORY] = $this->getImagesDirectory();

        return $this->getPdfReportBase(self::TWIG_FILE, $reportData, false);
    }

    /**
     * @param int $year
     * @param \DateTime $pedigreeActiveEndDateLimit
     * @return string
     */
    private function getSqlQuery(\DateTime $sampleDate, int $locationId)
    {
        return "SELECT
            va.uln,
            va.stn,
            va.animal_order_number as werknummer,
            va.gender as geslacht,
            va.dd_mm_yyyy_date_of_birth as geboorte_datum,
            (CASE WHEN va.gender = 'MALE' THEN
                  va.dd_mm_yyyy_date_of_birth
                ELSE null END) as datum_aanvoer,
            (CASE WHEN va.gender = 'NEUTER' THEN
                  '693084'
                ELSE null END) as vorig_ubn,
            (CASE WHEN va.gender = 'FEMALE' THEN
                  va.dd_mm_yyyy_date_of_birth
                ELSE null END) as datum_afvoer,
            va.dd_mm_yyyy_date_of_death as datum_sterfte,
            'Slachtrijp/Weiderij' as reden_afvoer_of_sterfte
            FROM view_animal_livestock_overview_details va
            LIMIT 10
        ";
    }
}
