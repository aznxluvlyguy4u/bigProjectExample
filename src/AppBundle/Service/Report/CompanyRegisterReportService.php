<?php
namespace AppBundle\Service\Report;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Option\CompanyRegisterReportOptions;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Constant\TranslationKey;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\ReportUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\Translation;

class CompanyRegisterReportService extends ReportServiceBase
{
    const TITLE = 'company_register_report';
    const TWIG_FILE = 'Report/company_register_report.html.twig';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;
    const FILE_NAME_REPORT_TYPE = 'COMPANY_REGISTER';

    const DATE_RESULT_NULL_REPLACEMENT = "-";

    /**
     * @param Person $person
     * @param Location $location
     * @param CompanyRegisterReportOptions $options
     * @return JsonResponse
     * @throws \Exception
     */
    public function getReport(Person $person, Location $location, CompanyRegisterReportOptions $options)
    {
        $this->filename = $this->getCompanyRegisterFileName($location, $options);
        $this->folderName = self::FOLDER_NAME;
        $this->extension = $options->getFileType();

        if ($options->getFileType() === FileType::CSV) {
            return $this->generateCsvFileBySqlQuery(
                $this->getFilename(),
                $this->getRecordsSqlQuery($location, $options),
                []
            );
        }

        return $this->getPdfReport($person, $location, $options);
    }

    private function getCompanyRegisterFileName(Location $location, CompanyRegisterReportOptions $options): string {
        return ReportUtil::translateFileName($this->translator, self::FILE_NAME_REPORT_TYPE)
            . '_'.$location->getUbn() . '_' .
            ReportUtil::translateFileName($this->translator, TranslationKey::REFERENCE_DATE).
            $options->getSampleDateString(). '__' .
            ReportUtil::translateFileName($this->translator, TranslationKey::GENERATED_ON);
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
        $reportData['animals'] = $this->conn->query(self::getRecordsSqlQuery($location, $options))->fetchAll();
        $reportData['summary'] = $this->getReportSummaryData($options->getSampleDate(), $location->getId());
        $reportData[ReportLabel::IMAGES_DIRECTORY] = $this->getImagesDirectory();

        return $this->getPdfReportBase(self::TWIG_FILE, $reportData, true);
    }

    private function getRecordsSqlQuery(Location $location, CompanyRegisterReportOptions $options)
    {
        $ubn = $location->getUbn();
        $sampleDateString = $options->getSampleDateString();
        $toCharDateFormat = "'".SqlUtil::TO_CHAR_DATE_FORMAT."'";

        $activeRequestStateTypes = SqlUtil::activeRequestStateTypesJoinedList();
        $genderTranslationValues = SqlUtil::genderTranslationValues();

        $reasonOfLossTranslationValues = SqlUtil::reasonOfLossOrDepartTranslationValues(
            Translation::getReasonOfLossTranslations($this->translator)
        );

        $reasonOfDepartTranslationValues = SqlUtil::reasonOfLossOrDepartTranslationValues(
            Translation::getReasonOfDepartTranslations($this->translator)
        );

        $animalOrderNumberLabel = ReportUtil::translateColumnHeader($this->translator, JsonInputConstant::ANIMAL_ORDER_NUMBER);
        $dateOfBirthLabel = ReportUtil::translateColumnHeader($this->translator,JsonInputConstant::DATE_OF_BIRTH);
        $genderLabel = ReportUtil::translateColumnHeader($this->translator,JsonInputConstant::GENDER);

        return "SELECT
    CONCAT(uln_country_code,uln_number) as uln,
    CONCAT(pedigree_country_code,pedigree_number) as stn,
    a.animal_order_number as $animalOrderNumberLabel,
    gender.dutch as $genderLabel,
    to_char(a.date_of_birth, $toCharDateFormat) as $dateOfBirthLabel,
    -- a.ubn_of_birth,
    (CASE WHEN arrival.animal_id NOTNULL THEN
              arrival.arrival_date
          WHEN a.ubn_of_birth = '$ubn' THEN
              to_char(a.date_of_birth, $toCharDateFormat)
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
        to_char(arrival_date, $toCharDateFormat) as arrival_date,
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
        to_char(d.depart_date, $toCharDateFormat) as depart_date,
        COALESCE(reason.dutch,d.reason_of_depart) as reason_of_depart -- If dutch translation cannot be found, use raw value
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
        to_char(dl.date_of_death, $toCharDateFormat) as date_of_death,
        COALESCE(reason.dutch,dl.reason_of_loss) as reason_of_loss -- If dutch translation cannot be found, use raw value
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
