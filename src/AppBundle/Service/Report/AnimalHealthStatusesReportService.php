<?php


namespace AppBundle\Service\Report;


use AppBundle\Enumerator\FileType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\ResultUtil;

class AnimalHealthStatusesReportService extends ReportServiceBase
{
    const TITLE = 'animal_health_statuses_report';
    const FOLDER_NAME = self::TITLE;
    const FILENAME = self::TITLE;

    /**
     * @inheritDoc
     */
    function getReport()
    {
        try {

            $this->setFileNameValues();

            return $this->generateCsvFileBySqlQuery(
                $this->getFilename(),
                $this->getQuery(),
                $this->getBooleanColumns()
            );

        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }


    /**
     * @param string $referenceYear
     */
    private function setFileNameValues()
    {
        $this->filename = $this->translate(self::FILENAME)
            .'__'.$this->translate('generated on');
        $this->extension = FileType::CSV;
    }


    /**
     * @return array
     */
    private function getBooleanColumns()
    {
        return ['is_handmatige_wijziging_status_zwoegerziekte'];
    }

    /**
     * @return string
     * @throws \Exception
     */
    private static function getQuery(): string {
        return "SELECT
             l.ubn,
             vl.owner_full_name as naam_eigenaar,
             c.company_name as bedrijfsnaam,
             vl.city as ubn_stad,
             lc.code as ubn_land,
             vl.pedigree_register_abbreviations as stamboeklidmaatschappen,
             vl.breeder_numbers as fokkernummers,
             actuele_gezondheidsstatussen.actuele_status_zwoegerziekte,
             actuele_gezondheidsstatussen.actuele_status_cae,
             actuele_gezondheidsstatussen.actuele_status_cl,
             actuele_gezondheidsstatussen.actuele_status_scrapie,
             actuele_gezondheidsstatussen.actuele_status_rotkreupel,
             animal_counts_per_location.ewe_one_year_or_older as aantal_ooien_1_jaar_of_ouder,
             animal_counts_per_location.ram_one_year_or_older as aantal_rammen_1_jaar_of_ouder,
             animal_counts_per_location.neuter_one_year_or_older as aantal_onbekenden_1_jaar_of_ouder,
             animal_counts_per_location.ewe_younger_than_one_year as aantal_ooien_jonger_dan_1_jaar,
             animal_counts_per_location.ram_younger_than_one_year as aantal_rammen_jonger_dan_1_jaar,
             animal_counts_per_location.neuter_younger_than_one_year as aantal_onbekenden_jonger_dan_1_jaar,
             animal_counts_per_location.animals_missing_date_of_birth as aantal_dieren_zonder_geboortedatum,
             animal_counts_per_location.total_animal_count as totaal_aantal_dieren_op_stallijst,
             arr_arr_count.non_arr_arr_count as niet_arr_arr_dieren_aantal,
             c.subscription_date as start_lidmaatschap_diergezondheid,
             maedi_visna_details.max_log_date as datum_laatste_wijziging_status_zwoegerziekte,
             COALESCE(maedi_visna_details.is_manual_edit, FALSE) as is_handmatige_wijziging_status_zwoegerziekte,
             maedi_visna_details.reason_of_edit as reden_wijziging_status_zwoegerziekte,
             cl_details.max_log_date as datum_laatste_wijziging_status_cl,
             COALESCE(cl_details.is_manual_edit, FALSE) as is_handmatige_wijziging_status_cl,
             cl_details.reason_of_edit as reden_wijziging_status_cl,
             cae_details.max_log_date as datum_laatste_wijziging_status_cae,
             COALESCE(cae_details.is_manual_edit, FALSE) as is_handmatige_wijziging_status_cae,
             cae_details.reason_of_edit as reden_wijziging_status_cae,
             arrival_details.count_arrivals_last_12_months as aantal_aanvoeren_laatste_12_maanden,
             import_details.count_imports_last_12_months as aantal_imports_laatste_12_maanden,
             -- When a status was changed to NOT free/resistant
             maedi_visna_health_status_demotion.demotion_duration
              as duration_maedi_visna_health_status_demotion,
             caseous_lymphadenitis_health_status_demotion.demotion_duration
               as duration_caseous_lymphadenitis_health_status_demotion,
             'data_niet_beschikbaar'
               as duration_cae_health_status_demotion,
             scrapie_health_status_demotion.demotion_duration
               as duration_scrapie_health_status_demotion,       
             foot_rot_health_status_demotion.demotion_duration
               as duration_foot_rot_health_status_demotion,
             -- When a status was changed to free/resistant but in the last 12 months had a demotion
             maedi_visna_health_status_promotion_with_demotion_in_last_12_months.promotion_duration
              as duration_maedi_visna_health_status_promotion_with_demotion_in_last_12_months,
             caseous_lymphadenitis_health_status_promotion_with_demotion_in_last_12_months.promotion_duration
               as duration_caseous_lymphadenitis_health_status_promotion_with_demotion_in_last_12_months,
             'data_niet_beschikbaar'
               as duration_cae_health_status_promotion_with_demotion_in_last_12_months,
             scrapie_health_status_promotion_with_demotion_in_last_12_months.promotion_duration
               as duration_scrapie_health_status_promotion_with_demotion_in_last_12_months,       
             foot_rot_health_status_promotion_with_demotion_in_last_12_months.promotion_duration
               as duration_foot_rot_health_status_promotion_with_demotion_in_last_12_months,
             -- When any status promotion or demotion took place
             last_maedi_visna.date_last_change_maedi_visna as datum_laatste_wijziging_zwoegerziekte,
             null as datum_laatste_wijziging_cae,
             last_caseous_lymphadenitis.date_last_change_caseous_lymphadenitis as datum_laatste_wijziging_cl,
             last_scrapie.date_last_change_scrapie as datum_laatste_wijziging_scrapie,
             last_foot_rot.date_last_change_foot_rot as datum_laatste_wijziging_rotkreupel,
             'geen data' as datum_volgende_bloedonderzoek_zwoegerziekte,
             'geen data' as datum_volgende_bloedonderzoek_cae,
             'geen data' as datum_volgende_bloedonderzoek_cl
            FROM location l
              INNER JOIN company c ON l.company_id = c.id
              INNER JOIN address la ON la.id = l.address_id
              INNER JOIN country lc ON lc.id = la.country_details_id
              INNER JOIN view_location_details vl ON vl.location_id = l.id
              INNER JOIN location_health lh on l.location_health_id = lh.id
              LEFT JOIN (
                  ".self::selectQueryCurrentHealthStatuses()."
                ) actuele_gezondheidsstatussen ON actuele_gezondheidsstatussen.location_health_id = l.location_health_id
            LEFT JOIN (
              ".self::selectQueryAnimalCountsPerLocation()."
            )as animal_counts_per_location ON animal_counts_per_location.location_id = l.id
            LEFT JOIN (
                ".self::selectQueryNonArrArrAnimalCountPerLocation()."
              )arr_arr_count ON arr_arr_count.location_id = l.id
            LEFT JOIN (
                ".self::selectQueryMaediVisnaDetails()."
              )maedi_visna_details ON maedi_visna_details.location_health_id = lh.id
            LEFT JOIN (
                ".self::selectQueryCaseousLymphadenitisDetails()."
              )cl_details ON cl_details.location_health_id = lh.id
            LEFT JOIN (
                ".self::selectQueryCaeDetails()."
              )cae_details ON cae_details.location_health_id = lh.id
            LEFT JOIN (
              ".self::selectQueryArrivalDetails()."
            )arrival_details ON arrival_details.location_id = l.id
            LEFT JOIN (--import
              ".self::selectQueryImportDetails()."
            )import_details ON import_details.location_id = l.id
            LEFT JOIN (
                ".self::selectQueryLastIllness('maedi_visna')."
              )last_maedi_visna ON last_maedi_visna.location_health_id = lh.id
              LEFT JOIN (
                ".self::selectQueryLastIllness('scrapie')."
              )last_scrapie ON last_scrapie.location_health_id = lh.id
              LEFT JOIN (
                ".self::selectQueryLastIllness('foot_rot')."
              )last_foot_rot ON last_foot_rot.location_health_id = lh.id
              LEFT JOIN (
              ".self::selectQueryLastIllness('caseous_lymphadenitis')."
            )last_caseous_lymphadenitis ON last_caseous_lymphadenitis.location_health_id = lh.id
            LEFT JOIN (
              ".self::illnessHealthStatusPromotionWithDemotionInLast12Months('maedi_visna')."
              )maedi_visna_health_status_promotion_with_demotion_in_last_12_months ON
              maedi_visna_health_status_promotion_with_demotion_in_last_12_months.location_health_id = l.location_health_id
            LEFT JOIN (
              ".self::illnessHealthStatusPromotionWithDemotionInLast12Months('scrapie')."
              )scrapie_health_status_promotion_with_demotion_in_last_12_months ON
              scrapie_health_status_promotion_with_demotion_in_last_12_months.location_health_id = l.location_health_id
            LEFT JOIN (
              ".self::illnessHealthStatusPromotionWithDemotionInLast12Months('foot_rot')."
              )foot_rot_health_status_promotion_with_demotion_in_last_12_months ON
              foot_rot_health_status_promotion_with_demotion_in_last_12_months.location_health_id = l.location_health_id
            LEFT JOIN (
              ".self::illnessHealthStatusPromotionWithDemotionInLast12Months('caseous_lymphadenitis')."
              )caseous_lymphadenitis_health_status_promotion_with_demotion_in_last_12_months ON
              caseous_lymphadenitis_health_status_promotion_with_demotion_in_last_12_months.location_health_id = l.location_health_id
            LEFT JOIN (
              ".self::illnessHealthStatusDemotion('maedi_visna')."
              )maedi_visna_health_status_demotion ON
              maedi_visna_health_status_demotion.location_health_id = l.location_health_id
            LEFT JOIN (
              ".self::illnessHealthStatusDemotion('scrapie')."
              )scrapie_health_status_demotion ON
              scrapie_health_status_demotion.location_health_id = l.location_health_id
            LEFT JOIN (
              ".self::illnessHealthStatusDemotion('foot_rot')."
              )foot_rot_health_status_demotion ON
              foot_rot_health_status_demotion.location_health_id = l.location_health_id
            LEFT JOIN (
              ".self::illnessHealthStatusDemotion('caseous_lymphadenitis')."
              )caseous_lymphadenitis_health_status_demotion ON
              caseous_lymphadenitis_health_status_demotion.location_health_id = l.location_health_id;"
            ;
    }


    private static function selectQueryCurrentHealthStatuses(): string {
        return "SELECT
            lh.id as location_health_id,
            maedi_visna_health_status.nl as actuele_status_zwoegerziekte,
            scrapie_health_status.nl as actuele_status_scrapie,
            caseous_lymphadenitis_health_status.nl as actuele_status_cl,
            'CAE_status_voor_geiten_onbekend' as actuele_status_cae,
            footrot_health_status.nl as actuele_status_rotkreupel
            FROM location_health lh
                 LEFT JOIN (
            ".self::healthStatusTranslationValues()."
            ) AS maedi_visna_health_status(en, nl) ON lh.current_maedi_visna_status = maedi_visna_health_status.en
                 LEFT JOIN (
            ".self::healthStatusTranslationValues()."
            ) AS scrapie_health_status(en, nl) ON lh.current_scrapie_status = scrapie_health_status.en
                 LEFT JOIN (
            ".self::healthStatusTranslationValues()."
            ) AS footrot_health_status(en, nl) ON lh.current_foot_rot_status = footrot_health_status.en
                 LEFT JOIN (
            ".self::healthStatusTranslationValues()."
            ) AS caseous_lymphadenitis_health_status(en, nl) ON lh.current_caseous_lymphadenitis_status = caseous_lymphadenitis_health_status.en";
    }


    private static function healthStatusTranslationValues(): string{
        return " VALUES
            ('BLANK', null),
            ('FREE', 'Vrij'),
            ('FREE 1 YEAR', 'Vrij 1 Jaar'),
            ('FREE_1_YEAR', 'Vrij 1 Jaar'),
            ('FREE 2 YEAR', 'Vrij 2 Jaar'),
            ('FREE_2_YEAR', 'Vrij 2 Jaar'),
            ('NOT_SUSPECT_LEVEL_1', 'NIET VERDACHT NIVEAU 1'),
            ('NOT_SUSPECT_LEVEL_2', 'NIET VERDACHT NIVEAU 2'),
            ('NOT_SUSPECT_LEVEL_3', 'NIET VERDACHT NIVEAU 3'),
            ('NOT SUSPECT LEVEL 1', 'NIET VERDACHT NIVEAU 1'),
            ('NOT SUSPECT LEVEL 2', 'NIET VERDACHT NIVEAU 2'),
            ('NOT SUSPECT LEVEL 3', 'NIET VERDACHT NIVEAU 3'),
            ('RESISTANT', 'Resistant'),
            ('STATUS KNOWN BY AHD', 'Resistant'),
            ('STATUS KNOWN BY AHD', 'Status bij GD bekend'),
            ('STATUS KNOWN BY ANIMAL HEALTH DEPARTMENT', 'Status bij GD bekend'),
            ('STATUS_KNOWN_BY_ANIMAL_HEALTH_DEPARTMENT', 'Status bij GD bekend'),
            ('UNDER OBSERVATION', 'In observatie'),
            ('UNDER_OBSERVATION', 'In observatie'),
            ('UNDER INVESTIGATION', 'In onderzoek'),
            ('UNDER_INVESTIGATION', 'In onderzoek'),
            ('UNKNOWN', 'Onbekend') ";
    }

    private static function selectQueryAnimalCountsPerLocation(): string {
        return "SELECT
            location_id,
            SUM(count) FILTER ( WHERE type = 'Ewe' AND one_year_or_older ) AS ewe_one_year_or_older,
            SUM(count) FILTER ( WHERE type = 'Ram' AND one_year_or_older ) AS ram_one_year_or_older,
            SUM(count) FILTER ( WHERE type = 'Neuter' AND one_year_or_older ) AS neuter_one_year_or_older,
            SUM(count) FILTER ( WHERE type = 'Ewe' AND one_year_or_older = FALSE ) AS ewe_younger_than_one_year,
            SUM(count) FILTER ( WHERE type = 'Ram' AND one_year_or_older = FALSE ) AS ram_younger_than_one_year,
            SUM(count) FILTER ( WHERE type = 'Neuter' AND one_year_or_older = FALSE ) AS neuter_younger_than_one_year,
            SUM(count) FILTER ( WHERE one_year_or_older ISNULL ) AS animals_missing_date_of_birth,
            SUM(count) AS total_animal_count
            FROM (
                 SELECT
                   location_id,
                   type,
                   EXTRACT(YEAR FROM AGE(current_date, date_of_birth)) > 0 as one_year_or_older,
                   COUNT(*) as count
                 FROM animal
                    WHERE location_id NOTNULL
                 GROUP BY location_id, type, one_year_or_older
               )as raw
            GROUP BY location_id";
    }

    private static function selectQueryNonArrArrAnimalCountPerLocation(): string {
        return "SELECT
        location_id,
        COUNT(*) as non_arr_arr_count
        FROM animal
        WHERE (
          scrapie_genotype ISNULL OR
          scrapie_genotype <> 'ARR/ARR'
        ) AND location_id NOTNULL
        GROUP BY location_id";
    }


    private static function selectQueryMaediVisnaDetails(): string {
        return "SELECT
            maedi_visna.location_health_id,
            maedi_visna.log_date as max_log_date,
            maedi_visna.reason_of_edit,
            COALESCE(maedi_visna.is_manual_edit, FALSE) as is_manual_edit
        FROM maedi_visna
            INNER JOIN (
            SELECT
              location_health_id,
              MAX(id) as max_id
            FROM maedi_visna
            GROUP BY location_health_id
            )last_maedi_visna ON last_maedi_visna.max_id = maedi_visna.id
        AND last_maedi_visna.location_health_id = maedi_visna.location_health_id";
    }

    private static function selectQueryCaseousLymphadenitisDetails(): string {
        return "SELECT
            cl.location_health_id,
            cl.log_date as max_log_date,
            cl.reason_of_edit,
            COALESCE(cl.is_manual_edit, FALSE) as is_manual_edit
        FROM caseous_lymphadenitis cl
            INNER JOIN (
            SELECT
              location_health_id,
              MAX(id) as max_id
            FROM caseous_lymphadenitis cl
            GROUP BY location_health_id
            )last_cl ON last_cl.max_id = cl.id
        AND last_cl.location_health_id = cl.location_health_id";
    }

    private static function selectQueryCaeDetails(): string {
        return "SELECT
            cae.location_health_id,
            cae.log_date as max_log_date,
            cae.reason_of_edit,
            COALESCE(cae.is_manual_edit, FALSE) as is_manual_edit
        FROM cae
            INNER JOIN (
            SELECT
              location_health_id,
              MAX(id) as max_id
            FROM cae
            GROUP BY location_health_id
            )last_cae ON last_cae.max_id = cae.id
        AND last_cae.location_health_id = cae.location_health_id";
    }

    private static function selectQueryArrivalDetails(): string {
        return "  SELECT
            location_id,
            COUNT(*) as count_arrivals_last_12_months
        FROM declare_arrival arrival
          INNER JOIN declare_base db on arrival.id = db.id
        WHERE
            (request_state = '".RequestStateType::FINISHED."' OR  request_state = '".RequestStateType::FINISHED_WITH_WARNING."') AND
            (
                (
                    EXTRACT(YEAR FROM AGE(current_date, arrival_date)) = 0
                ) OR
                (
                    EXTRACT(YEAR FROM AGE(current_date, arrival_date)) = 1 AND
                    EXTRACT(MONTH FROM AGE(current_date, arrival_date)) = 0
                )
            )
        GROUP BY arrival.location_id";
    }


    private static function selectQueryImportDetails(): string {
        return "SELECT
            location_id,
            COUNT(*) as count_imports_last_12_months
        FROM declare_import import
             INNER JOIN declare_base db on import.id = db.id
        WHERE
            (request_state = 'FINISHED' OR  request_state = 'FINISHED_WITH_WARNING') AND
            (
                (
                    EXTRACT(YEAR FROM AGE(current_date, import_date)) = 0
                ) OR
                (
                    EXTRACT(YEAR FROM AGE(current_date, import_date)) = 1 AND
                    EXTRACT(MONTH FROM AGE(current_date, import_date)) = 0
                )
            )
        GROUP BY import.location_id";
    }


    /**
     * @param string $illness
     * @return string
     * @throws \Exception
     */
    private static function selectQueryLastIllness(string $illness): string {
        self::validateIllnessVariable($illness);

        return "SELECT
            last_$illness.location_health_id,
            last_$illness.log_date as date_last_change_$illness
        FROM $illness last_$illness
        INNER JOIN (
           SELECT
             location_health_id,
             MAX(id) as last_id
           FROM $illness i
           GROUP BY location_health_id
        )max ON max.last_id = last_$illness.id";
    }

    private static function validateIllnessVariable(string $illness) {
        if ($illness == 'cae') {
            throw new \Exception("cae is not implemented yet in database");
        }

        if (
            $illness !== 'maedi_visna' &&
            $illness !== 'scrapie' &&
            $illness !== 'foot_rot' &&
            $illness !== 'caseous_lymphadenitis'
        ) {
            throw new \Exception("Invalid illness type");
        }
    }

    private static function illnessHealthStatusPromotionWithDemotionInLast12Months(string $illness): string {
        self::validateIllnessVariable($illness);

        return "SELECT
            last_free_$illness.location_health_id,
            last_free_$illness.log_date as date_last_free_$illness,
            last_free_$illness.status,
            illness_before_last_free_one.date_before_last_free_$illness,
            illness_before_last_free_one.status,
            last_free_$illness.log_date::date - illness_before_last_free_one.date_before_last_free_$illness::date as promotion_duration
        FROM $illness last_free_$illness
            INNER JOIN (
        SELECT
            location_health_id,
            MAX(id) as last_id
        FROM $illness i
        WHERE ".self::freeIllnessStatusOrList()."
        GROUP BY location_health_id
        )max ON max.last_id = last_free_$illness.id
            INNER JOIN (
        SELECT
            illness_before_last_free_one.location_health_id,
            illness_before_last_free_one.log_date as date_before_last_free_$illness,
            illness_before_last_free_one.status
        FROM $illness illness_before_last_free_one
            INNER JOIN (
        SELECT
            i.location_health_id,
            max(i.id) as id_before_free_$illness
        FROM $illness i
            INNER JOIN (
        SELECT
            i.location_health_id,
            MAX(id) as max_id_of_each_location_healh
        FROM $illness i
        WHERE ".self::freeIllnessStatusOrList()."
        GROUP BY location_health_id
        )j ON j.location_health_id = i.location_health_id AND i.id < j.max_id_of_each_location_healh
        WHERE NOT ".self::freeIllnessStatusOrList()."
        GROUP BY i.location_health_id
        )d ON d.id_before_free_$illness = illness_before_last_free_one.id
            AND d.location_health_id = illness_before_last_free_one.location_health_id
        )illness_before_last_free_one ON
            illness_before_last_free_one.location_health_id = last_free_$illness.location_health_id
        WHERE
        (--\"Wanneer de status weer is aangepast naar vrij maar de afgelopen 12 maanden wel een aanpassing had gehad
          -- waarbij de status naar niet-vrij/niet-resistent werd gezet\"
            (
                EXTRACT(YEAR FROM AGE(current_date, illness_before_last_free_one.date_before_last_free_$illness)) = 0
            ) OR
            (
                EXTRACT(YEAR FROM AGE(current_date, illness_before_last_free_one.date_before_last_free_$illness)) = 1 AND
                EXTRACT(MONTH FROM AGE(current_date, illness_before_last_free_one.date_before_last_free_$illness)) = 0
            )
        )";
    }


    private static function freeIllnessStatusOrList(): string {
        return "( -- is vrij of resistent
            i.status = 'FREE 1 YEAR' OR
            i.status = 'FREE_1_YEAR' OR
            i.status = 'FREE 2 YEAR' OR
            i.status = 'FREE_2_YEAR' OR
            i.status = 'FREE' OR
            i.status = 'RESISTANT'
        )";
    }

    private static function illnessHealthStatusDemotion(string $illness): string {
        self::validateIllnessVariable($illness);

        return "SELECT
            last_non_free_$illness.location_health_id,
            last_non_free_$illness.log_date as date_last_non_free_$illness,
            last_non_free_$illness.status,
            illness_before_last_non_free_one.date_before_last_non_free_$illness,
            illness_before_last_non_free_one.status,
            last_non_free_$illness.log_date::date - illness_before_last_non_free_one.date_before_last_non_free_$illness::date as demotion_duration
        FROM $illness last_non_free_$illness
        INNER JOIN (
            SELECT
              location_health_id,
              MAX(id) as last_id
            FROM $illness i
            WHERE NOT ".self::freeIllnessStatusOrList()."
            GROUP BY location_health_id
        )max ON max.last_id = last_non_free_$illness.id
            INNER JOIN (
            SELECT
            illness_before_last_non_free_one.location_health_id,
            illness_before_last_non_free_one.log_date as date_before_last_non_free_$illness,
            illness_before_last_non_free_one.status
            FROM $illness illness_before_last_non_free_one
                 INNER JOIN (
            SELECT
              i.location_health_id,
              max(i.id) as id_before_non_free_$illness
            FROM $illness i
                   INNER JOIN (
              SELECT
                i.location_health_id,
                MAX(id) as max_id_of_each_location_healh
              FROM $illness i
              WHERE NOT ".self::freeIllnessStatusOrList()."
              GROUP BY location_health_id
            )j ON j.location_health_id = i.location_health_id AND i.id < j.max_id_of_each_location_healh
            WHERE ".self::freeIllnessStatusOrList()."
            GROUP BY i.location_health_id
            )d ON d.id_before_non_free_$illness = illness_before_last_non_free_one.id
            AND d.location_health_id = illness_before_last_non_free_one.location_health_id
            ) illness_before_last_non_free_one ON
            illness_before_last_non_free_one.location_health_id = last_non_free_$illness.location_health_id
            ";
    }
}