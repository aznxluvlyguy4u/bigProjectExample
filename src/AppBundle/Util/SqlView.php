<?php


namespace AppBundle\Util;


use AppBundle\Traits\EnumInfo;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableExistsException;

class SqlView
{
    use EnumInfo;

    const VIEW_ANIMAL_LIVESTOCK_OVERVIEW_DETAILS = 'view_animal_livestock_overview_details';
    const VIEW_LOCATION_DETAILS = 'view_location_details';
    const VIEW_MINIMAL_PARENT_DETAILS = 'view_minimal_parent_details';
    const VIEW_PEDIGREE_REGISTER_ABBREVIATION = 'view_pedigree_register_abbreviation';
    const VIEW_PERSON_FULL_NAME = 'view_person_full_name';


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
            case self::VIEW_LOCATION_DETAILS: return self::locationDetails();
            case self::VIEW_MINIMAL_PARENT_DETAILS: return self::minimalParentDetails();
            case self::VIEW_PEDIGREE_REGISTER_ABBREVIATION: return self::pedigreeRegisterAbbreviation();
            case self::VIEW_PERSON_FULL_NAME: return self::personFullName();
            default: return null;
        }
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
                          WHERE c.code = 'TE'
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
                c.animal_id,
                CONCAT(a.uln_country_code, a.uln_number) as uln,
                CONCAT(a.pedigree_country_code, a.pedigree_number) as stn,
                to_char(a.date_of_birth, 'DD-MM-YYYY') as dd_mm_yyyy_date_of_birth,
                c.general_appearance,
                c.muscularity,
                NULLIF(trim(trailing '-ling' from c.n_ling),'') as n_ling,
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
                a_breed_types.dutch_first_letter as breed_type_as_dutch_first_letter
              FROM animal a
                LEFT JOIN animal_cache c ON c.animal_id = a.id
                LEFT JOIN (VALUES (true, '*'),(false, '')) AS production_asterisk_dad(bool_val, mark)
                  ON c.gave_birth_as_one_year_old = production_asterisk_dad.bool_val
                LEFT JOIN (VALUES
                ".SqlUtil::breedTypeFirstLetterOnlyTranslationValues()."
                          ) AS a_breed_types(english, dutch_first_letter) ON a.breed_type = a_breed_types.english
                LEFT JOIN (VALUES
                ".SqlUtil::createSqlValuesString(Translation::getEnglishPredicateToAbbreviationArray())."
                ) AS predicate(english, abbreviation) ON predicate.english = a.predicate";
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
                NULLIF(trim(trailing '-ling' from c.n_ling),'') as n_ling,
                a.gender,
                a.animal_order_number,
                a.ubn_of_birth,
                a.location_of_birth_id,
                a.location_id,
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
                
                pr.abbreviation as pedigree_register_abbreviation
                
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
                ) AS predicate(english, abbreviation) ON predicate.english = a.predicate";
    }
}