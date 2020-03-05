<?php


namespace AppBundle\Util;


use AppBundle\Enumerator\RequestStateType;
use AppBundle\Traits\EnumInfo;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableExistsException;

class SqlView
{
    use EnumInfo;

    const VIEW_PERSON_FULL_NAME = 'view_person_full_name';
    const VIEW_ANIMAL_LIVESTOCK_OVERVIEW_DETAILS = 'view_animal_livestock_overview_details';
    const VIEW_ANIMAL_HISTORIC_LOCATIONS = 'view_animal_historic_locations';
    const VIEW_ANIMAL_IS_PUBLIC = 'view_animal_is_public';
    const VIEW_SCAN_MEASUREMENTS = 'view_scan_measurements';
    const VIEW_EWE_LITTER_AGE = 'view_ewe_litter_age';
    const VIEW_LITTER_DETAILS = 'view_litter_details';
    const VIEW_LOCATION_DETAILS = 'view_location_details';
    const VIEW_NAME_AND_ADDRESS_DETAILS = 'view_name_and_address_details';
    const VIEW_MINIMAL_PARENT_DETAILS = 'view_minimal_parent_details';
    const VIEW_PEDIGREE_REGISTER_ABBREVIATION = 'view_pedigree_register_abbreviation';
    const VIEW_BREED_VALUE_MAX_GENERATION_DATE = 'view_breed_value_max_generation_date';


    /**
     * @param Connection $conn
     * @param $viewName
     * @return boolean
     */
    public static function createOrUpdateView(Connection $conn, $viewName)
    {
        try {
            $sql = "CREATE OR REPLACE VIEW $viewName AS
                ".self::getSelectQuery($viewName);
            $conn->exec($sql);

        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }


    /**
     * @param Connection $conn
     * @param $viewName
     * @return boolean
     */
    public static function createViewIfNotExists(Connection $conn, $viewName)
    {
        try {
            $sql = "CREATE VIEW $viewName AS
                ".self::getSelectQuery($viewName);
            $conn->exec($sql);

        } catch (TableExistsException $exception) {
            return false;

        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }


    /**
     * @param Connection $conn
     * @param $viewName
     * @return bool
     */
    public static function dropView(Connection $conn, $viewName)
    {
        try {
            $sql = "DROP VIEW $viewName";
            $conn->exec($sql);

        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }


    /**
     * @param string $viewName
     * @return string|null
     */
    public static function getSelectQuery($viewName)
    {
        switch ($viewName) {
            case self::VIEW_ANIMAL_LIVESTOCK_OVERVIEW_DETAILS: return self::animalLiveStockOverviewDetails();
            case self::VIEW_ANIMAL_IS_PUBLIC: return self::isPublicAnimalQuery();
            case self::VIEW_ANIMAL_HISTORIC_LOCATIONS: return self::animalResidenceQuery();
            case self::VIEW_LITTER_DETAILS: return self::litterDetails();
            case self::VIEW_EWE_LITTER_AGE: return self::eweLitterAge();
            case self::VIEW_LOCATION_DETAILS: return self::locationDetails();
            case self::VIEW_NAME_AND_ADDRESS_DETAILS: return self::nameAndAddressDetailsQuery();
            case self::VIEW_MINIMAL_PARENT_DETAILS: return self::minimalParentDetails();
            case self::VIEW_PEDIGREE_REGISTER_ABBREVIATION: return self::pedigreeRegisterAbbreviation();
            case self::VIEW_BREED_VALUE_MAX_GENERATION_DATE: return self::breedValueMaxGenerationDate();
            case self::VIEW_PERSON_FULL_NAME: return self::personFullName();
            case self::VIEW_SCAN_MEASUREMENTS: return self::scanMeasurements();
            default: return null;
        }
    }


    /**
     * @return string
     */
    private static function litterDetails()
    {
        return "SELECT
                  l.litter_id,
                  r.id as pedigree_register_registration_id,
                  l.breeder_number,
                  l.breed_code,
                  l.location_of_birth_id,
                  l.ubn_of_birth,
                  litter.status = '".RequestStateType::COMPLETED."' as is_completed
                FROM (
                  SELECT
                    litter_id,
                    MAX(NULLIF(breed_code,'')) as breed_code,
                    MAX(NULLIF(NULLIF(SUBSTR(pedigree_number, 1,5),''),'00000')) as breeder_number,
                    MAX(location_of_birth_id) as location_of_birth_id,
                    MAX(NULLIF(ubn_of_birth,'')) as ubn_of_birth
                  FROM animal a
                  GROUP BY litter_id
                )l
                LEFT JOIN pedigree_register_registration r ON r.breeder_number = l.breeder_number
                INNER JOIN litter ON litter.id = l.litter_id";
    }


    /**
     * @return string
     */
    private static function locationDetails()
    {
        return
            "SELECT
              l.id as location_id,
              l.ubn as ubn,
              NULLIF(TRIM(CONCAT(p.first_name,' ',p.last_name)),'') as owner_full_name,
              a.city as city,
              a.state as state,
              prs.pedigree_register_abbreviations,
              te_prs.te_pedigree_register_abbreviations,
              prs.breeder_numbers,
              te_prs.te_breeder_numbers
            FROM location l
              INNER JOIN company c ON l.company_id = c.id
              INNER JOIN person p ON p.id = c.owner_id
              INNER JOIN address a ON a.id = c.address_id
              LEFT JOIN (
                          SELECT
                            prr.location_id,
                            TRIM(BOTH '{,}' FROM CAST(array_agg(pr.abbreviation ORDER BY abbreviation) AS TEXT)) as pedigree_register_abbreviations,
                            TRIM(BOTH '{,}' FROM CAST(array_agg(prr.breeder_number ORDER BY breeder_number) AS TEXT)) as breeder_numbers
                          FROM pedigree_register_registration prr
                            INNER JOIN pedigree_register pr ON prr.pedigree_register_id = pr.id
                          WHERE prr.is_active
                          GROUP BY location_id
                        )prs ON prs.location_id = l.id
              LEFT JOIN (
                          SELECT
                            prr.location_id,
                            TRIM(BOTH '{,}' FROM CAST(array_agg(pr.abbreviation ORDER BY abbreviation) AS TEXT)) as te_pedigree_register_abbreviations,
                            TRIM(BOTH '{,}' FROM CAST(array_agg(prr.breeder_number ORDER BY breeder_number) AS TEXT)) as te_breeder_numbers
                          FROM pedigree_register_registration prr
                            INNER JOIN pedigree_register pr ON prr.pedigree_register_id = pr.id
                            INNER JOIN pedigree_register_pedigree_codes codes ON pr.id = codes.pedigree_register_id
                            INNER JOIN pedigree_code c ON codes.pedigree_code_id = c.id
                          WHERE c.code = 'TE' AND prr.is_active
                          GROUP BY location_id
                        )te_prs ON te_prs.location_id = l.id";
    }


    /**
     * @return string
     */
    private static function pedigreeRegisterAbbreviation()
    {
        return
            "SELECT
                r.id as pedigree_register_id,
                r.abbreviation
             FROM pedigree_register r";
    }


    /**
     * @return string
     */
    private static function personFullName()
    {
        return
            "SELECT
                p.id as person_id,
                NULLIF(TRIM(CONCAT(p.first_name,' ',p.last_name)),'') as full_name
              FROM person p";
    }


    /**
     * @return string
     */
    private static function minimalParentDetails()
    {
        return
            "SELECT
                a.id as animal_id,
                CONCAT(a.uln_country_code, a.uln_number) as uln,
                CONCAT(a.pedigree_country_code, a.pedigree_number) as stn,
                to_char(a.date_of_birth, 'DD-MM-YYYY') as dd_mm_yyyy_date_of_birth,
                c.general_appearance,
                c.muscularity,
                NULLIF(COALESCE(NULLIF(trim(trailing '-ling' from c.n_ling),''), CAST(a.n_ling AS TEXT)),'') as n_ling,
                NULLIF(CONCAT(
                           COALESCE(CAST(c.production_age AS TEXT), '-'),'/',
                           COALESCE(CAST(c.litter_count AS TEXT), '-'),'/',
                           COALESCE(CAST(c.total_offspring_count AS TEXT), '-'),'/',
                           COALESCE(CAST(c.born_alive_offspring_count AS TEXT), '-'),
                           production_asterisk_dad.mark
                       ),'-/-/-/-') as production,
                NULLIF(CONCAT(predicate.abbreviation, NULLIF(CONCAT('(',GREATEST(a.predicate_score,13),')'), '(13)')),'') as formatted_predicate,
                a.breed_code,
                a.scrapie_genotype,
                a_breed_types.dutch_first_letter as breed_type_as_dutch_first_letter,
                a.location_of_birth_id
              FROM animal a
                LEFT JOIN location l ON a.location_id = l.id
                LEFT JOIN animal_cache c ON c.animal_id = a.id
                LEFT JOIN (VALUES (true, '*'),(false, '')) AS production_asterisk_dad(bool_val, mark)
                  ON c.gave_birth_as_one_year_old = production_asterisk_dad.bool_val
                LEFT JOIN (VALUES
                ".SqlUtil::breedTypeFirstLetterOnlyTranslationValues()."
                          ) AS a_breed_types(english, dutch_first_letter) ON a.breed_type = a_breed_types.english
                LEFT JOIN (VALUES
                ".SqlUtil::createSqlValuesString(Translation::getEnglishPredicateToAbbreviationArray())."
                ) AS predicate(english, abbreviation) ON predicate.english = a.predicate
                ";
    }


    /**
     * @return string
     */
    private static function animalLiveStockOverviewDetails()
    {
        return
            "SELECT
                a.id as animal_id,
                a.parent_mother_id,
                a.parent_father_id,
                CONCAT(a.uln_country_code, a.uln_number) as uln,
                CONCAT(a.pedigree_country_code, a.pedigree_number) as stn,
                a.date_of_birth,
                a.is_alive,
                to_char(a.date_of_birth, '".DateUtil::DEFAULT_SQL_DATE_STRING_FORMAT."') as dd_mm_yyyy_date_of_birth,
                to_char(a.date_of_death, '".DateUtil::DEFAULT_SQL_DATE_STRING_FORMAT."') as dd_mm_yyyy_date_of_death,
                NULLIF(COALESCE(NULLIF(trim(trailing '-ling' from c.n_ling),''), CAST(a.n_ling AS TEXT)),'') as n_ling,
                a.gender,
                a.animal_order_number,
                a.ubn_of_birth,
                a.location_of_birth_id,
                a.location_id,
                a.scan_measurement_set_id,
                NULLIF(CONCAT(
                           COALESCE(CAST(c.production_age AS TEXT), '-'),'/',
                           COALESCE(CAST(c.litter_count AS TEXT), '-'),'/',
                           COALESCE(CAST(c.total_offspring_count AS TEXT), '-'),'/',
                           COALESCE(CAST(c.born_alive_offspring_count AS TEXT), '-'),
                           production_asterisk_dad.mark
                       ),'-/-/-/-') as production,
                NULLIF(CONCAT(predicate.abbreviation, NULLIF(CONCAT('(',GREATEST(a.predicate_score,13),')'), '(13)')),'') as formatted_predicate,
                a.breed_code,
                a.scrapie_genotype,
                a_breed_types.dutch_first_letter as breed_type_as_dutch_first_letter,
                
                to_char(c.exterior_measurement_date, '".DateUtil::DEFAULT_SQL_DATE_STRING_FORMAT."') as dd_mm_yyyy_exterior_measurement_date,
                NULLIF(c.kind,'') as kind,
                c.skull as skull,
                c.progress as progress,
                c.muscularity as muscularity,
                c.proportion as proportion,
                c.exterior_type as exterior_type,                 
                c.leg_work as leg_work,
                c.fur as fur,
                c.general_appearance as general_appearance,
                c.height as height,
                c.breast_depth as breast_depth,
                c.torso_length as torso_length,
                p.full_name as exterior_inspector_full_name,
                
                pr.abbreviation as pedigree_register_abbreviation,
                
                tail_length.length as tail_length,
                to_char(tail_length.measurement_date, '".DateUtil::DEFAULT_SQL_DATE_STRING_FORMAT."') as dd_mm_yyyy_tail_length_measurement_date,
                muscle_thickness.muscle_thickness as muscle_thickness,
                to_char(muscle_thickness.measurement_date, '".DateUtil::DEFAULT_SQL_DATE_STRING_FORMAT."') as dd_mm_yyyy_muscle_thickness_measurement_date,          
                body_fat.fat1 as fat1,
                body_fat.fat2 as fat2,
                body_fat.fat3 as fat3,
                to_char(body_fat.measurement_date, '".DateUtil::DEFAULT_SQL_DATE_STRING_FORMAT."') as dd_mm_yyyy_body_fat_measurement_date,
                 
                COALESCE(child_status.has_children_as_mom, FALSE) as has_children_as_mom
                
              FROM animal a
                LEFT JOIN animal_cache c ON c.animal_id = a.id
                LEFT JOIN (VALUES (true, '*'),(false, '')) AS production_asterisk_dad(bool_val, mark)
                  ON c.gave_birth_as_one_year_old = production_asterisk_dad.bool_val
                LEFT JOIN (VALUES
                ".SqlUtil::breedTypeFirstLetterOnlyTranslationValues()."
                          ) AS a_breed_types(english, dutch_first_letter) ON a.breed_type = a_breed_types.english
                LEFT JOIN view_person_full_name p ON p.person_id = c.exterior_inspector_id
                LEFT JOIN view_pedigree_register_abbreviation pr ON pr.pedigree_register_id = a.pedigree_register_id
                LEFT JOIN (VALUES
                ".SqlUtil::createSqlValuesString(Translation::getEnglishPredicateToAbbreviationArray())."
                ) AS predicate(english, abbreviation) ON predicate.english = a.predicate
                LEFT JOIN (
                    SELECT
                      m.animal_id,
                      m.length,
                      m3.measurement_date
                    FROM tail_length m
                      INNER JOIN (
                                   SELECT
                                     m.animal_id,
                                     MAX(m.id) as max_id
                                   FROM tail_length m
                                     INNER JOIN measurement m2 ON m.id = m2.id
                                   WHERE m2.is_active
                                   GROUP BY m.animal_id
                                 )g ON g.max_id = m.id
                      INNER JOIN measurement m3 ON m.id = m3.id
                )tail_length ON tail_length.animal_id = a.id
                
                LEFT JOIN (
                    SELECT
                      m.animal_id,
                      m.muscle_thickness,
                      m3.measurement_date
                    FROM muscle_thickness m
                      INNER JOIN (
                                   SELECT
                                     m.animal_id,
                                     MAX(m.id) as max_id
                                   FROM muscle_thickness m
                                     INNER JOIN measurement m2 ON m.id = m2.id
                                   WHERE m2.is_active
                                   GROUP BY m.animal_id
                                 )g ON g.max_id = m.id
                      INNER JOIN measurement m3 ON m.id = m3.id
                )muscle_thickness ON muscle_thickness.animal_id = a.id
                
                LEFT JOIN (
                    SELECT
                      m.animal_id,
                      f1.fat as fat1,
                      f2.fat as fat2,
                      f3.fat as fat3,
                      m3.measurement_date
                    FROM body_fat m
                      INNER JOIN (
                                   SELECT
                                     m.animal_id,
                                     MAX(m.id) as max_id
                                   FROM body_fat m
                                     INNER JOIN measurement m2 ON m.id = m2.id
                                   WHERE m2.is_active
                                   GROUP BY m.animal_id
                                 )g ON g.max_id = m.id
                      INNER JOIN fat1 f1 ON m.fat1_id = f1.id
                      INNER JOIN fat2 f2 ON m.fat2_id = f2.id
                      INNER JOIN fat3 f3 ON m.fat3_id = f3.id
                      INNER JOIN measurement m3 ON m.id = m3.id
                )body_fat ON body_fat.animal_id = a.id
                LEFT JOIN (
                  SELECT
                    mom.id,
                    COUNT(mom.id) > 0 as has_children_as_mom
                  FROM animal mom
                    INNER JOIN animal child ON child.parent_mother_id = mom.id
                  GROUP BY mom.id
                )child_status ON child_status.id = a.id
                ";
    }


    /**
     * @return string
     */
    private static function animalResidenceQuery(): string
    {
        return "SELECT
                    residence.animal_id,
                    CONCAT(uln_country_code, uln_number) as uln,
                    residence.historic_location_ids,
                    residence.historic_ubns
                FROM animal a
                    INNER JOIN (
                        SELECT
                            r.animal_id,
                            -- If you want to remove the curly brackets use the following code
                            -- TRIM(BOTH '{,}' FROM CAST(array_agg(l.ubn ORDER BY ubn) AS TEXT)) as historic_ubns,
                            -- TRIM(BOTH '{,}' FROM CAST(array_agg(r.location_id ORDER BY r.location_id) AS TEXT)) as historic_location_ids
                            REPLACE( REPLACE( CAST(array_agg(l.ubn ORDER BY ubn) AS TEXT),'{', '['), '}', ']') as historic_ubns,
                            REPLACE( REPLACE( CAST(array_agg(r.location_id ORDER BY r.location_id) AS TEXT) ,'{', '['), '}', ']') as historic_location_ids
                        FROM (
                                 SELECT
                                     r.animal_id,
                                     r.location_id
                                 FROM animal_residence r
                                 GROUP BY r.animal_id, r.location_id
                
                                 UNION
                
                                 SELECT
                                     a.id as animal_id,
                                     a.location_id
                                 FROM animal a
                                 WHERE a.location_id NOTNULL
                             ) r
                                 INNER JOIN location l on r.location_id = l.id
                        GROUP BY r.animal_id
                    )residence ON residence.animal_id = a.id";
    }


    /**
     * @return string
     */
    private static function isPublicAnimalQuery(): string
    {
        return "SELECT
                    public_status.animal_id,
                    CONCAT(uln_country_code, uln_number) as uln,
                    public_status.is_public
                FROM animal a
                    INNER JOIN (
                        SELECT
                            r.animal_id,
                            TRUE = ANY(array_agg(is_reveal_historic_animals)::boolean[]) as is_public
                        FROM (
                                 SELECT
                                     r.animal_id,
                                     r.location_id,
                                     c.is_reveal_historic_animals
                                 FROM animal_residence r
                                          INNER JOIN location l on r.location_id = l.id
                                          INNER JOIN (
                                     SELECT id, is_reveal_historic_animals
                                     FROM company WHERE is_active
                                 )c ON l.company_id = c.id
                                 GROUP BY r.animal_id, r.location_id, c.is_reveal_historic_animals
                
                                 UNION
                
                                 SELECT
                                     a.id as animal_id,
                                     a.location_id,
                                     c.is_reveal_historic_animals
                                 FROM animal a
                                          INNER JOIN location l on a.location_id = l.id
                                          INNER JOIN (
                                     SELECT id, is_reveal_historic_animals
                                     FROM company WHERE is_active
                                 )c ON l.company_id = c.id
                                 GROUP BY a.id, a.location_id, c.is_reveal_historic_animals
                             ) r
                                 INNER JOIN location l on r.location_id = l.id
                        GROUP BY r.animal_id
                    )public_status ON public_status.animal_id = a.id";
    }


    /**
     * @return string
     */
    private static function nameAndAddressDetailsQuery(): string
    {
        return "SELECT
                  c.id as company_id,
                  c.owner_id,
                  TRIM(CONCAT(owner.first_name,' ',owner.last_name)) as company_owner_full_name,
                  owner.email_address as company_owner_email_address,
                  owner.cellphone_number as company_owner_cellphone_number,
                  c.telephone_number as company_telephone_number,
                  TRIM(CONCAT(ca.street_name,' ',ca.address_number,ca.address_number_suffix)) as company_address,
                  ca.postal_code as company_postal_code,
                  ca.city as company_city,
                  country.code as company_country_code,
                  country.id as company_country_id,
                  owner.is_active as owner_is_active,
                  c.is_active as company_is_active
                FROM company c
                  LEFT JOIN address ca ON ca.id = c.address_id
                  LEFT JOIN person owner ON owner.id = c.owner_id
                  LEFT JOIN country on ca.country_details_id = country.id";
    }


    private static function scanMeasurements(): string {
        return "SELECT
    s.animal_id,
    m.log_date,
    m.measurement_date,
    to_char(m.measurement_date, 'DD-MM-YYYY') as dd_mm_yyyy_measurement_date,
    w_details.is_active as is_scan_weight_active,
    w.weight,
    t.muscle_thickness,
    fat1.fat as fat1,
    fat2.fat as fat2,
    fat3.fat as fat3,
    m.inspector_id,
    NULLIF(TRIM(CONCAT(inspector.first_name,' ',inspector.last_name)),'') as inspector,
    m.action_by_id,
    NULLIF(TRIM(CONCAT(action_by.first_name,' ',action_by.last_name)),'') as action_by
FROM scan_measurement_set s
        LEFT JOIN measurement m ON m.id = s.id
        LEFT JOIN weight w ON w.id = s.scan_weight_id
        LEFT JOIN measurement w_details ON w_details.id = w.id
        LEFT JOIN muscle_thickness t ON t.id = s.muscle_thickness_id
        LEFT JOIN body_fat bf ON bf.id = s.body_fat_id
        LEFT JOIN fat1 ON bf.fat1_id = fat1.id
        LEFT JOIN fat2 ON bf.fat2_id = fat2.id
        LEFT JOIN fat3 ON bf.fat3_id = fat3.id
        LEFT JOIN person inspector ON inspector.id = m.inspector_id
        LEFT JOIN person action_by ON action_by.id = m.action_by_id";
    }


    private static function breedValueMaxGenerationDate(): string
    {
        return "SELECT
       to_char(generation_date, 'DD-MM-YYYY') as dd_mm_yyyy,
       generation_date::date as date,
       generation_date as date_time,
       DATE_PART('year', generation_date) as year
FROM breed_value WHERE generation_date NOTNULL ORDER BY id DESC LIMIT 1";
    }

    private static function eweLitterAge(): string
    {
        return "SELECT
    mom.id as ewe_id,
    mom.date_of_birth,
    litter.litter_date,
    EXTRACT(YEAR FROM AGE(litter.litter_date, mom.date_of_birth)) as date_accurate_years,
    EXTRACT(YEAR FROM AGE(litter.litter_date, mom.date_of_birth)) * 12 +
    EXTRACT(MONTH FROM AGE(litter.litter_date, mom.date_of_birth)) as date_accurate_months,
    EXTRACT(DAYS FROM (litter.litter_date - mom.date_of_birth)) / 365 * 12 as day_standardized_months,
    EXTRACT(DAYS FROM (litter.litter_date - mom.date_of_birth)) / 365 as day_standardized_years,
    EXTRACT(DAYS FROM (litter.litter_date - mom.date_of_birth)) as days
FROM litter
    INNER JOIN animal mom ON mom.id = litter.animal_mother_id
    INNER JOIN (
        SELECT
            animal_mother_id,
            MAX(standard_litter_ordinal) as max_standard_litter_ordinal
        FROM litter
        GROUP BY animal_mother_id
    )last_litter ON last_litter.max_standard_litter_ordinal = litter.standard_litter_ordinal
 AND last_litter.animal_mother_id = litter.animal_mother_id
 WHERE mom.date_of_birth NOTNULL and litter.litter_date NOTNULL";
    }
}
