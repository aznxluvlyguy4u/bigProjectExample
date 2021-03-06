<?php


namespace AppBundle\Service\Report;


use AppBundle\Constant\TranslationKey;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\FileType;
use AppBundle\Util\ReportUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\SqlUtil;
use Symfony\Component\HttpFoundation\Response;

class WeightsPerYearOfBirthReportService extends ReportServiceBase
{
    const TITLE = 'weights_per_year_of_birth';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;
    const FILE_NAME_REPORT_TYPE = 'WEIGHTS PER YEAR OF BIRTH';

    const AVERAGES_DECIMAL_COUNT = 2;
    const PERCENTAGES_DECIMAL_COUNT = 0;

    /**
     * @param $yearOfBirth
     * @param Location|null $location
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    function getReport($yearOfBirth, ?Location $location = null)
    {
        if (!ctype_digit($yearOfBirth) && !is_int($yearOfBirth)) {
            return ResultUtil::errorResult("Year is not an integer", Response::HTTP_BAD_REQUEST);
        }

        $yearOfBirthAsInt = intval($yearOfBirth);

        try {
            $this->filename = $this->getWeightsPerYearOfBirthFileName($location);
            $this->extension = FileType::CSV;

            return $this->generateCsvFileBySqlQuery(
                $this->getFilename(),
                $this->getSqlQuery($yearOfBirthAsInt, $location),
                []
            );
        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }

    private function getWeightsPerYearOfBirthFileName($location): string {
        $ubn = is_null($location) ? "" : $location->getUbn() . '_';
        return ReportUtil::translateFileName($this->translator, self::FILE_NAME_REPORT_TYPE)
            . '_'. $ubn .
            ReportUtil::translateFileName($this->translator, TranslationKey::GENERATED_ON);
    }


    private static function locationFilter(?Location $location, bool $includeDeclareWeightCheck): string {
        $locationId = $location ? $location->getId() : null;

        $declareWeightCheck = "";
        if ($includeDeclareWeightCheck) {
            $activeRequestStateTypes = SqlUtil::activeRequestStateTypesJoinedList();
            $declareWeightCheck = "w.id IN (
                        SELECT weight_measurement_id
                        FROM declare_weight w
                                 INNER JOIN declare_nsfo_base dnb on w.id = dnb.id
                        WHERE w.location_id = $locationId AND dnb.request_state IN ($activeRequestStateTypes)
                        GROUP BY weight_measurement_id
                    )
                    OR";
        }

        return $locationId ?
            "AND (
                    a.location_id = $locationId
                    OR
                    a.location_of_birth_id = $locationId
                    OR
                    $declareWeightCheck
                    a.id IN (
                            SELECT animal_id
                            FROM animal_residence
                            WHERE a.location_id = $locationId AND is_pending = FALSE
                            GROUP BY animal_id
                        )
                ) -- location filter (for user) "
            : "";
    }


    /**
     * @param int $yearOfBirth
     * @param Location|null $location
     * @return string
     */
    private function getSqlQuery(int $yearOfBirth, ?Location $location = null)
    {
        $primaryAnimalQueryFilter = "     AND date_part('year', a.date_of_birth) = $yearOfBirth -- Year filter (for user and admin)
                            ";
        $primaryWeightQueryFilter = $primaryAnimalQueryFilter . self::locationFilter($location, true);

        $dateFormat = "'".SqlUtil::TO_CHAR_DATE_FORMAT."'";

        return "SELECT
            va.uln,
            NULLIF(va.stn,'') as stn,
            va.animal_order_number as werknummer,
            va.dd_mm_yyyy_date_of_birth as geboortedatum,
            va.n_ling,
            va.breed_code as rascode,
            va.breed_type_as_dutch_first_letter as rasstatus,
            va.pedigree_register_abbreviation as stamboek,
            NULLIF(CONCAT(father.pedigree_country_code,father.pedigree_number),'') as stn_vader,
            NULLIF(CONCAT(mother.pedigree_country_code,mother.pedigree_number),'') as stn_moeder,
            to_char(birth_weight_measurement.measurement_date, $dateFormat) as meting_datum_geboortegewicht,
            birth_weight_measurement.weight as geboortegewicht,
            to_char(weight_data.measurement_date_1, $dateFormat) as datum_meting_1,
            weight_data.weight_measurement_1 as gewicht_meting_1,
            to_char(weight_data.measurement_date_2,$dateFormat) as datum_meting_2,
            weight_data.weight_measurement_2 as gewicht_meting_2,
            to_char(weight_data.measurement_date_3, $dateFormat) as datum_meting_3,
            weight_data.weight_measurement_3 as gewicht_meting_3,
            to_char(weight_data.measurement_date_4, $dateFormat) as datum_meting_4,
            weight_data.weight_measurement_4 as gewicht_meting_4,
            to_char(weight_data.measurement_date_5, $dateFormat) as datum_meting_5,
            weight_data.weight_measurement_5 as gewicht_meting_5,
            to_char(weight_data.measurement_date_6, $dateFormat) as datum_meting_6,
            weight_data.weight_measurement_6 as gewicht_meting_6,
            to_char(weight_data.measurement_date_7, $dateFormat) as datum_meting_7,
            weight_data.weight_measurement_7 as gewicht_meting_7,
            to_char(weight_data.measurement_date_8, $dateFormat) as datum_meting_8,
            weight_data.weight_measurement_8 as gewicht_meting_8,
            to_char(weight_data.measurement_date_9, $dateFormat) as datum_meting_9,
            weight_data.weight_measurement_9 as gewicht_meting_9,
            to_char(weight_data.measurement_date_10, $dateFormat) as datum_meting_10,
            weight_data.weight_measurement_10 as gewicht_meting_10
        FROM animal a
            INNER JOIN view_animal_livestock_overview_details va ON va.animal_id = a.id
            INNER JOIN animal father ON father.id = a.parent_father_id
            INNER JOIN animal mother ON mother.id = a.parent_mother_id
            LEFT JOIN (
                SELECT
                    w.animal_id,
                    w.weight,
                    m.measurement_date
                FROM weight w
                         INNER JOIN measurement m ON m.id = w.id
                WHERE is_revoked = FALSE AND is_active AND is_birth_weight
                    -- Only the id's of all first birth weight on the first found measurement date
                    AND w.id IN (
                    SELECT
                        --w.animal_id,
                        MIN(w.id) as measurement_id
                    FROM weight w
                             INNER JOIN measurement m ON m.id = w.id
                             INNER JOIN (
                        -- Get the weight on the earliest birth weights
                        SELECT
                            animal_id, MIN(m.measurement_date) as min_measurement_date
                        FROM weight w
                                INNER JOIN animal a ON a.id = w.animal_id
                                 INNER JOIN measurement m on w.id = m.id
                        WHERE (is_revoked = FALSE AND is_active AND is_birth_weight)
                            $primaryWeightQueryFilter
                        GROUP BY w.animal_id
                    )first_date_birth_weight ON first_date_birth_weight.animal_id = w.animal_id AND first_date_birth_weight.min_measurement_date = m.measurement_date
                    WHERE is_revoked = FALSE AND is_active AND is_birth_weight
                    GROUP BY w.animal_id
                )
            )birth_weight_measurement ON birth_weight_measurement.animal_id = a.id
            LEFT JOIN (
                SELECT
                    weights.animal_id,
                    wd1.measurement_date as measurement_date_1,
                    w1.weight as weight_measurement_1,
                    wd2.measurement_date as measurement_date_2,
                    w2.weight as weight_measurement_2,
                    wd3.measurement_date as measurement_date_3,
                    w3.weight as weight_measurement_3,
                    wd4.measurement_date as measurement_date_4,
                    w4.weight as weight_measurement_4,
                    wd5.measurement_date as measurement_date_5,
                    w5.weight as weight_measurement_5,
                    wd6.measurement_date as measurement_date_6,
                    w6.weight as weight_measurement_6,
                    wd7.measurement_date as measurement_date_7,
                    w7.weight as weight_measurement_7,
                    wd8.measurement_date as measurement_date_8,
                    w8.weight as weight_measurement_8,
                    wd9.measurement_date as measurement_date_9,
                    w9.weight as weight_measurement_9,
                    wd10.measurement_date as measurement_date_10,
                    w10.weight as weight_measurement_10
                FROM (
                         SELECT
                             w.animal_id,
                             array_agg(w.id ORDER BY m.measurement_date) as weight_ids
                         FROM weight w
                            INNER JOIN animal a ON a.id = w.animal_id
                            INNER JOIN measurement m on w.id = m.id
                         WHERE is_revoked = FALSE AND is_active AND is_birth_weight = FALSE
                           $primaryWeightQueryFilter
                         GROUP BY animal_id
                     )weights
                         LEFT JOIN weight w1 ON w1.id = weights.weight_ids[1]
                         LEFT JOIN measurement wd1 ON wd1.id = weights.weight_ids[1]
                         LEFT JOIN weight w2 ON w2.id = weights.weight_ids[2]
                         LEFT JOIN measurement wd2 ON wd2.id = weights.weight_ids[2]
                         LEFT JOIN weight w3 ON w3.id = weights.weight_ids[3]
                         LEFT JOIN measurement wd3 ON wd3.id = weights.weight_ids[3]
                         LEFT JOIN weight w4 ON w4.id = weights.weight_ids[4]
                         LEFT JOIN measurement wd4 ON wd4.id = weights.weight_ids[4]
                         LEFT JOIN weight w5 ON w5.id = weights.weight_ids[5]
                         LEFT JOIN measurement wd5 ON wd5.id = weights.weight_ids[5]
                         LEFT JOIN weight w6 ON w6.id = weights.weight_ids[6]
                         LEFT JOIN measurement wd6 ON wd6.id = weights.weight_ids[6]
                         LEFT JOIN weight w7 ON w7.id = weights.weight_ids[7]
                         LEFT JOIN measurement wd7 ON wd7.id = weights.weight_ids[7]
                         LEFT JOIN weight w8 ON w8.id = weights.weight_ids[8]
                         LEFT JOIN measurement wd8 ON wd8.id = weights.weight_ids[8]
                         LEFT JOIN weight w9 ON w9.id = weights.weight_ids[9]
                         LEFT JOIN measurement wd9 ON wd9.id = weights.weight_ids[9]
                         LEFT JOIN weight w10 ON w10.id = weights.weight_ids[10]
                         LEFT JOIN measurement wd10 ON wd10.id = weights.weight_ids[10]
            )weight_data ON weight_data.animal_id = a.id
        WHERE (birth_weight_measurement.weight NOTNULL OR weight_data.weight_measurement_1 NOTNULL)
            $primaryAnimalQueryFilter
        ";
    }
}
