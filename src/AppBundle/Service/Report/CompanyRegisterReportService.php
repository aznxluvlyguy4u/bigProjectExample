<?php
namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Option\CompanyRegisterReportOptions;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\FileType;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\Translation;

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
                $this->getSqlQuery($location, $options),
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

    private function getSqlQuery(Location $location, CompanyRegisterReportOptions $options)
    {
        $ubn = $location->getUbn();
        $sampleDateString = $options->getSampleDate()->format(SqlUtil::DATE_FORMAT);

        $activeRequestStateTypes = SqlUtil::activeRequestStateTypesJoinedList();
        $genderTranslationValues = SqlUtil::genderTranslationValues();

        $reasonOfLossTranslationValues = SqlUtil::reasonOfLossOrDepartTranslationValues(
            Translation::getReasonOfLossTranslations($this->translator)
        );

        $reasonOfDepartTranslationValues = SqlUtil::reasonOfLossOrDepartTranslationValues(
            Translation::getReasonOfDepartTranslations($this->translator)
        );

        $animalOrderNumberLabel = strtolower($this->translator->trans(strtoupper(JsonInputConstant::ANIMAL_ORDER_NUMBER)));
        $dateOfBirthLabel = strtolower($this->translator->trans(strtoupper(JsonInputConstant::DATE_OF_BIRTH)));
        $genderLabel = strtolower($this->translator->trans(strtoupper(JsonInputConstant::GENDER)));

        return "SELECT
    CONCAT(uln_country_code,uln_number) as uln,
    CONCAT(pedigree_country_code,pedigree_number) as stn,
    a.animal_order_number as $animalOrderNumberLabel,
    gender.dutch as $genderLabel,
    a.date_of_birth as $dateOfBirthLabel,
    -- a.ubn_of_birth,
    (CASE WHEN arrival.animal_id NOTNULL THEN
              arrival.arrival_date
          WHEN a.ubn_of_birth = '$ubn' THEN
              a.date_of_birth
          ELSE null END) as datum_aanvoer,
    (CASE WHEN arrival.ubn_previous_owner NOTNULL THEN
              arrival.ubn_previous_owner
          ELSE null END) as vorig_ubn,
    (CASE WHEN loss.date_of_death ISNULL THEN
              depart.depart_date
          ELSE null END) as datum_afvoer,
    loss.date_of_death as datum_sterfte,
    (CASE WHEN loss.reason_of_loss ISNULL THEN
              depart.reason_of_depart
          ELSE loss.reason_of_loss END) as reden_afvoer_of_sterfte
FROM animal a
    LEFT JOIN (VALUES $genderTranslationValues) AS gender(english, dutch) ON a.type = gender.english
    LEFT JOIN (
    -- arrival data
    SELECT
        animal_id,
        arrival_date,
        ubn_previous_owner
    FROM declare_base b
             INNER JOIN declare_arrival da on b.id = da.id
             INNER JOIN location l on da.location_id = l.id
    WHERE l.ubn = '$ubn' AND
            b.request_state IN ($activeRequestStateTypes) AND
            b.id IN (
            -- Select the declare arrival with the highest arrivalDate before or on the referenceDate
            SELECT
                -- animal_id, --each declare id only represents one animal
                MAX(b.id) as max_declare_id
                -- it is assumed that newer declareArrivals always have an arrival_date greater or equal to the older declareArrivals
            FROM declare_base b
                     INNER JOIN declare_arrival da on b.id = da.id
                     INNER JOIN location l on da.location_id = l.id
            WHERE l.ubn = '$ubn' AND animal_id NOTNULL AND
                    b.request_state IN ($activeRequestStateTypes) AND
                    da.arrival_date < ('$sampleDateString'::date + '1 day'::interval)
            GROUP BY animal_id
        )
    )arrival ON arrival.animal_id = a.id
    LEFT JOIN (
    -- select declare depart if on or after reference date
    SELECT
        animal_id,
        d.depart_date,
        reason.dutch as reason_of_depart
        -- d.reason_of_depart
    FROM declare_base b
             INNER JOIN declare_depart d on b.id = d.id
             INNER JOIN location l on d.location_id = l.id
             LEFT JOIN (VALUES $reasonOfDepartTranslationValues) AS reason(english, dutch) ON d.reason_of_depart = reason.english
    WHERE l.ubn = '$ubn' AND
            b.request_state IN ($activeRequestStateTypes) AND
            b.id IN (
            -- Select the declare depart with the highest arrivalDate before or on the referenceDate
            SELECT
                -- animal_id, --each declare id only represents one animal
                MIN(b.id) as min_declare_id
                -- it is assumed that newer declareDeparts always have a depart_date greater or equal to the older declareDeparts
            FROM declare_base b
                     INNER JOIN declare_depart d on b.id = d.id
                     INNER JOIN location l on d.location_id = l.id
            WHERE l.ubn = '$ubn' AND animal_id NOTNULL AND
                    b.request_state IN ($activeRequestStateTypes) AND
                    ('$sampleDateString'::date - '1 day'::interval) < d.depart_date
            GROUP BY animal_id
        )
    )depart ON depart.animal_id = a.id
    LEFT JOIN (
    -- select declare loss if on or after reference date
    SELECT
        animal_id,
        dl.date_of_death,
        reason.dutch as reason_of_loss
        -- dl.reason_of_loss
    FROM declare_base b
             INNER JOIN declare_loss dl on b.id = dl.id
             INNER JOIN location l on dl.location_id = l.id
             LEFT JOIN (VALUES $reasonOfLossTranslationValues) AS reason(english, dutch) ON dl.reason_of_loss = reason.english
    WHERE l.ubn = '$ubn' AND
            b.request_state IN ($activeRequestStateTypes) AND
            b.id IN (
            -- Select the declare depart with the highest arrivalDate before or on the referenceDate
            SELECT
                -- animal_id, --each declare id only represents one animal
                MIN(b.id) as min_declare_id
                -- it is assumed that newer declareDeparts always have a depart_date greater or equal to the older declareDeparts
            FROM declare_base b
                     INNER JOIN declare_loss dl on b.id = dl.id
                     INNER JOIN location l on dl.location_id = l.id
            WHERE l.ubn = '$ubn' AND animal_id NOTNULL AND
                    b.request_state IN ($activeRequestStateTypes) AND
                    ('$sampleDateString'::date - '1 day'::interval) < dl.date_of_death
            GROUP BY animal_id
        )
    )loss ON loss.animal_id = a.id
WHERE a.id IN (
    -- List of animal_id's on location_id at given referenceDate
    SELECT
        animal_id
    FROM animal_residence ar
             INNER JOIN location l on ar.location_id = l.id
    WHERE is_pending = FALSE AND
            l.ubn = '$ubn' AND
      --animal is on location on a specific date
        (start_date < ('$sampleDateString'::date + '1 day'::interval) AND (end_date ISNULL OR (('$sampleDateString'::date - '1 day'::interval) < end_date)))
    GROUP BY animal_id
    )
;";
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
