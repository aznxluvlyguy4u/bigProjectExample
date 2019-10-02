<?php
namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Option\ClientNotesOverviewReportOptions;
use AppBundle\Component\Option\CompanyRegisterReportOptions;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Constant\TranslationKey;
use AppBundle\Entity\Company;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\ReportUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\Translation;

class ClientNotesOverviewReportService extends ReportServiceBase
{
    const TITLE = 'client_notes_overview_report';
    const TWIG_FILE = 'Report/client_notes_overview_report.html.twig';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;
    const FILE_NAME_REPORT_TYPE = 'CLIENT_NOTES_OVERVIEW_REPORT';

    const DATE_RESULT_NULL_REPLACEMENT = "-";

    /**
     * @param Person $person
     * @param Company $company
     * @param ClientNotesOverviewReportOptions $options
     */
    public function getReport(Person $person, Company $company, ClientNotesOverviewReportOptions $options)
    {
        $this->filename = $this->getClientNotesOverviewReportFileName($person, $company, $options);
        $this->folderName = self::FOLDER_NAME;
        $this->extension = $options->getFileType();

        if ($options->getFileType() === FileType::CSV) {
            return $this->generateCsvFileBySqlQuery(
                $this->getFilename(),
                $this->getRecordsSqlQuery($company, $options),
                []
            );
        }

        return $this->getPdfReport($person, $company, $options);
    }

    private function getClientNotesOverviewReportFileName(Person $person, Company $company, ClientNotesOverviewReportOptions $options): string {
        $ubnsString = implode("_", $company->getUbns());
        $fullnameString = str_replace(" ", "_", $person->getFullName());

        return ReportUtil::translateFileName($this->translator, self::FILE_NAME_REPORT_TYPE)
            . '_' . $ubnsString . '_' . $fullnameString . '__' .
            ReportUtil::translateFileName($this->translator, TranslationKey::GENERATED_ON);
    }

    /**
     * @param Person $person
     * @param Location $location
     * @param ClientNotesOverviewReportOptions $options
     * @return JsonResponse
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getPdfReport(Person $person, Company $company, ClientNotesOverviewReportOptions $options)
    {
        $reportData = [];
//        $reportData['sampleDate'] = $options->getSampleDate();
//        $reportData['person'] = $person;
//        $reportData['location'] = $location;
//        $reportData['animals'] = $this->conn->query(self::getRecordsSqlQuery($location, $options))->fetchAll();
//        $reportData['summary'] = $this->getReportSummaryData($options->getSampleDate(), $location->getId());
//        $reportData[ReportLabel::IMAGES_DIRECTORY] = $this->getImagesDirectory();

        return $this->getPdfReportBase(self::TWIG_FILE, $reportData, true);
    }

    private function getRecordsSqlQuery(Company $company, ClientNotesOverviewReportOptions $options)
    {
        $companyId = $company->getId();

//        $ubn = $location->getUbn();
//        $sampleDateString = $options->getSampleDateString();
//        $toCharDateFormat = "'".SqlUtil::TO_CHAR_DATE_FORMAT."'";
//
//        $activeRequestStateTypes = SqlUtil::activeRequestStateTypesJoinedList();
//        $genderTranslationValues = SqlUtil::genderTranslationValues();
//
//        $reasonOfLossTranslationValues = SqlUtil::reasonOfLossOrDepartTranslationValues(
//            Translation::getReasonOfLossTranslations($this->translator)
//        );
//
//        $reasonOfDepartTranslationValues = SqlUtil::reasonOfLossOrDepartTranslationValues(
//            Translation::getReasonOfDepartTranslations($this->translator)
//        );
//
//        $animalOrderNumberLabel = ReportUtil::translateColumnHeader($this->translator, JsonInputConstant::ANIMAL_ORDER_NUMBER);
//        $dateOfBirthLabel = ReportUtil::translateColumnHeader($this->translator,JsonInputConstant::DATE_OF_BIRTH);
//        $genderLabel = ReportUtil::translateColumnHeader($this->translator,JsonInputConstant::GENDER);

        return "SELECT
            note.creation_date::date as datum,
            note.creation_date::time as tijd,
            TRIM(concat(p.first_name,' ',p.last_name)) as medewerker,
            ubns.ubns,
            company_name as bedrijfsnaam,
            a.city as bedrijf_plaats,
            pedigree.pedigree_register_abbreviations as stamboeken,
            pedigree.breeder_numbers as fokker_nummers,
            COALESCE(c.animal_health_subscription, false) as diergezondheidsprogramma,
            note.note as notitie
        FROM company_note note
            INNER JOIN company c ON c.id = note.company_id
            LEFT JOIN address a ON a.id = c.address_id
            LEFT JOIN person p ON p.id = note.creator_id
            LEFT JOIN (
                SELECT
                    company_id,
                    TRIM(BOTH '{,}' FROM CAST(array_agg(l.ubn ORDER BY l.ubn) AS TEXT)) as ubns
                FROM location l
                WHERE l.is_active
                GROUP BY company_id
            )ubns ON ubns.company_id = c.id
            LEFT JOIN (
                SELECT
                    company_id,
                    TRIM(BOTH '{,}' FROM CAST(array_agg(r.breeder_number ORDER BY r.breeder_number) AS TEXT)) as breeder_numbers,
                    TRIM(BOTH '{,}' FROM CAST(array_agg(p.abbreviation ORDER BY p.abbreviation) AS TEXT)) as pedigree_register_abbreviations
                FROM pedigree_register_registration r
                         INNER JOIN location l on r.location_id = l.id
                         INNER JOIN pedigree_register p on p.id = r.pedigree_register_id
                WHERE r.is_active AND l.is_active
                GROUP BY company_id
            )pedigree ON pedigree.company_id = c.id
        WHERE note.company_id = $companyId
        ORDER BY note.creation_date DESC";
    }

    /**
     * @param \DateTime $sampleDate
     * @param int $locationId
     * @return array
     */
    private function getReportSummaryData(\DateTime $sampleDate, int $locationId): array
    {
        $results = $this->getAnimalCounts($sampleDate, $locationId);
        $results['latest_sync_date_rvo_leading'] = $this->getLatestSyncDate($locationId,true);
        $results['latest_sync_date'] = $this->getLatestSyncDate($locationId,false);

        $formattedResults= [];
        $formattedResults[0] = $results;
        return $formattedResults;
    }

    private function getAnimalCounts(\DateTime $sampleDate, int $locationId): array {
        $sampleDateString = "'".$sampleDate->format(SqlUtil::DATE_FORMAT)."'";

        $sql = "SELECT
    to_char($sampleDateString::date, '".SqlUtil::TO_CHAR_DATE_FORMAT."') as reference_date,
    to_char(current_date, '".SqlUtil::TO_CHAR_DATE_FORMAT."') as log_date,
    COALESCE(SUM(count) FILTER ( WHERE type = 'Ewe' AND one_year_or_older ), 0) AS ewes_one_year_or_older,
    COALESCE(SUM(count) FILTER ( WHERE type = 'Ram' AND one_year_or_older ), 0) AS rams_one_year_or_older,
    COALESCE(SUM(count) FILTER ( WHERE type = 'Neuter' AND one_year_or_older ), 0) AS neuters_one_year_or_older,
    COALESCE(SUM(count) FILTER ( WHERE type = 'Ewe' AND one_year_or_older = FALSE ), 0) AS ewes_younger_than_one_year,
    COALESCE(SUM(count) FILTER ( WHERE type = 'Ram' AND one_year_or_older = FALSE ), 0) AS rams_younger_than_one_year,
    COALESCE(SUM(count) FILTER ( WHERE type = 'Neuter' AND one_year_or_older = FALSE ), 0) AS neuters_younger_than_one_year,
    COALESCE(SUM(count) FILTER ( WHERE type = 'Ewe' AND one_year_or_older ISNULL ), 0) AS ewes_missing_date_of_birth,
    COALESCE(SUM(count) FILTER ( WHERE type = 'Ram' AND one_year_or_older ISNULL ), 0) AS rams_missing_date_of_birth,
    COALESCE(SUM(count) FILTER ( WHERE type = 'Neuter' AND one_year_or_older ISNULL ), 0) AS neuters_missing_date_of_birth,
    COALESCE(SUM(count) FILTER ( WHERE type = 'Ewe' ), 0) AS total_ewes,
    COALESCE(SUM(count) FILTER ( WHERE type = 'Ram' ), 0) AS total_rams,
    COALESCE(SUM(count) FILTER ( WHERE type = 'Neuter' ), 0) AS total_neuters,
    COALESCE(SUM(count) FILTER ( WHERE one_year_or_older ), 0) AS total_one_year_or_older,
    COALESCE(SUM(count) FILTER ( WHERE one_year_or_older = FALSE ), 0) AS total_younger_than_one_year,
    COALESCE(SUM(count) FILTER ( WHERE one_year_or_older ISNULL ), 0) AS total_missing_date_of_birth,
    COALESCE(SUM(count) FILTER ( WHERE one_year_or_older ISNULL ), 0) AS animals_missing_date_of_birth,
    COALESCE(SUM(count), 0) AS total_animal_count_on_reference_date
FROM (
         SELECT type,
                EXTRACT(YEAR FROM AGE($sampleDateString::date, date_of_birth)) > 0 as one_year_or_older,
                COUNT(*)                                                      as count
         FROM animal
         WHERE id IN (
             SELECT animal_id
             FROM animal_residence ar
                      INNER JOIN location l on ar.location_id = l.id
                      INNER JOIN animal a on ar.animal_id = a.id
             WHERE is_pending = FALSE
               AND ar.location_id = $locationId
               AND
               --animal is on location on a specific date
                 (start_date < ($sampleDateString::date + '1 day'::interval) AND
                  (end_date ISNULL OR (($sampleDateString::date - '1 day'::interval) < end_date)))
             GROUP BY animal_id
         )
         GROUP BY type, one_year_or_older
     ) as animal_counts;";
        return $this->conn->query($sql)->fetch();
    }


    private function getLatestSyncDate(int $locationId, bool $mustBeRvoLeading): string {
        $isRvoLeadingFilter = $mustBeRvoLeading ? " AND is_rvo_leading ": "";
        $sql = "SELECT
                    to_char(log_date, '".SqlUtil::TO_CHAR_DATE_FORMAT."') as log_date
                FROM retrieve_animals
                WHERE location_id = $locationId AND request_state = '".RequestStateType::FINISHED."'
                   $isRvoLeadingFilter
                ORDER BY id DESC LIMIT 1";
        $result = $this->conn->query($sql)->fetch();
        if (!is_array($result)) {
            return self::DATE_RESULT_NULL_REPLACEMENT;
        }
        return ArrayUtil::get('log_date', $result, self::DATE_RESULT_NULL_REPLACEMENT);
    }
}
