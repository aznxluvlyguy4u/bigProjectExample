<?php
namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Option\ClientNotesOverviewReportOptions;
use AppBundle\Constant\TranslationKey;
use AppBundle\Entity\Company;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\FileType;
use AppBundle\Util\ReportUtil;

class ClientNotesOverviewReportService extends ReportServiceBase
{
    const TITLE = 'client_notes_overview_report';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;
    const FILE_NAME_REPORT_TYPE = 'CLIENT_NOTES_OVERVIEW_REPORT';
    const DATE_RESULT_NULL_REPLACEMENT = "-";

    /**
     * @param Person $person
     * @param ClientNotesOverviewReportOptions $options
     * @return JsonResponse
     * @throws \Exception
     */
    public function getReport(Person $person, ClientNotesOverviewReportOptions $options)
    {
        $company = empty($options->getCompanyId()) ? null : $this->em->getRepository(Company::class)
            ->findOneByCompanyId($options->getCompanyId());

        $this->filename = $this->getClientNotesOverviewReportFileName($person, $company);
        $this->folderName = self::FOLDER_NAME;
        $this->extension = $options->getFileType();

        ReportUtil::validateFileType($this->extension, self::allowedFileTypes(), $this->translator);

        return $this->generateCsvFileBySqlQuery(
            $this->getFilename(),
            $this->getRecordsSqlQuery($options, $company),
            self::booleanColumns()
        );
    }

    private static function booleanColumns(): array {
        return [
            'diergezondheidsprogramma'
        ];
    }

    public static function allowedFileTypes(): array {
        return [
            FileType::CSV
        ];
    }

    private function getClientNotesOverviewReportFileName(?Person $person = null, ?Company $company = null): string {
        $ubnsString = is_null($company) ? ''  : '_' . implode("_", $company->getUbns());
        $fullnameString = is_null($person) ? ''  :  '_' . str_replace(" ", "_", $person->getFullName());

        return ReportUtil::translateFileName($this->translator, self::FILE_NAME_REPORT_TYPE)
            . $ubnsString . $fullnameString . '__' .
            ReportUtil::translateFileName($this->translator, TranslationKey::GENERATED_ON);
    }

    private function getRecordsSqlQuery(ClientNotesOverviewReportOptions $options, ?Company $company = null)
    {
        $startDateString = $options->getStartDateString();
        $endDateString = $options->getEndDateString();

        $creationDateFilter = " '$startDateString' <= DATE(note.creation_date) AND "
            ." DATE(note.creation_date) <= '$endDateString' ";

        $companyFilter = empty($company) ? "" : " AND note.company_id = ".$company->getId()." ";

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
                CONCAT('\"',note.note,'\"') as notitie
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
            WHERE
                  $creationDateFilter
                  $companyFilter
            ORDER BY note.creation_date DESC";
    }
}
