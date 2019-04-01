<?php
namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Option\CompanyRegisterReportOptions;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\FileType;

class CompanyRegisterReportService extends ReportServiceBase
{
    const TITLE = 'company_register_report';
    const TWIG_FILE = 'Report/company_register_report.html.twig';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;
    const FILE_NAME_REPORT_TYPE = 'COMPANY_REGISTER';

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
    }

    /**
     * @param Person $person
     * @param Location $location
     * @param CompanyRegisterReportOptions $options
     * @return JsonResponse
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getPdfReport(Person $person, Location $location, CompanyRegisterReportOptions $options)
    {
        $reportData = [];
        $reportData['sampleDate'] = $options->getSampleDate();
        $reportData['person'] = $person;
        $reportData['location'] = $location;
        $reportData['animals'] = $this->conn->query(self::getRecordsSqlQuery($options->getSampleDate(), $location->getId()))->fetchAll();
        $reportData['summary'] = $this->conn->query(self::getSummarySqlQuery($options->getSampleDate(), $location->getId()))->fetchAll();
        $reportData[ReportLabel::IMAGES_DIRECTORY] = $this->getImagesDirectory();

        return $this->getPdfReportBase(self::TWIG_FILE, $reportData, true);
    }

    /**
     * @param \DateTime $sampleDate
     * @param int $locationId
     * @return string
     */
    private function getRecordsSqlQuery(\DateTime $sampleDate, int $locationId)
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
            LIMIT 50
        ";
    }

    /**
     * @param \DateTime $sampleDate
     * @param int $locationId
     * @return string
     */
    private function getSummarySqlQuery(\DateTime $sampleDate, int $locationId)
    {
        return "SELECT
           230 as total_animal_count_on_reference_date,
           '01-02-2019' as reference_date,
           '27-03-2019' as log_date,
           10 as ewes_one_year_or_older,
           84 as rams_one_year_or_older,
           12 as neuters_one_year_or_older,
           10 as ewes_younger_than_one_year,
           12 as rams_younger_than_one_year,
           1 as neuters_younger_than_one_year,
           '24-03-2019' as latest_sync_date,
           '23-03-2019' as latest_sync_date_rvo_leading
        ";
    }
}
