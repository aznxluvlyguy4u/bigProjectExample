<?php


namespace AppBundle\Util;


use Doctrine\DBAL\Connection;

class StoredProcedure
{
    const GET_LIVESTOCK_REPORT = 'get_livestock_report';

    /**
     * @return array
     */
    public static function getConstants() {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }

    /**
     * @param Connection $conn
     * @param string $functionName
     * @param array $parameters
     * @return array
     */
    public static function getProcedure(Connection $conn, $functionName, array $parameters)
    {
        $parametersString = implode(',', $parameters);

        $conn->beginTransaction();

        $resultCursor = $conn->query("select $functionName($parametersString)")->fetch();
        $results = $conn->query("FETCH ALL IN \"" . $resultCursor[$functionName]."\"" )->fetchAll();

        $conn->commit();

        return $results;
    }


    /**
     * @param Connection $conn
     * @param string $functionName
     * @param string $query
     * @param array $parameters parameters by their type
     */
    private static function createOrUpdateProcedureBase(Connection $conn, $functionName, $query, array $parameters)
    {
        $parameterString = '';
        $prefix = '';

        foreach ($parameters as $parameter => $type)
        {
            $parameterString = $prefix . $parameter . ' ' . $type;
            $prefix = ', ';
        }

        $sql = "CREATE OR REPLACE FUNCTION $functionName($parameterString) RETURNS refcursor AS $$
                DECLARE ref refcursor;
                BEGIN
                  OPEN ref FOR $query;
                  RETURN ref;
                END;
                $$ LANGUAGE plpgsql";
        $conn->query($sql)->execute();
    }


    /**
     * @param Connection $conn
     * @param $functionName
     */
    public static function createOrUpdateProcedure(Connection $conn, $functionName)
    {
        switch ($functionName) {
            case self::GET_LIVESTOCK_REPORT: self::createLiveStockReport($conn); break;
            default: break;
        }
    }


    /**
     * @param Connection $conn
     */
    public static function createLiveStockReport(Connection $conn)
    {
        $sql = "SELECT DISTINCT CONCAT(a.uln_country_code, a.uln_number) as a_uln, CONCAT(a.pedigree_country_code, a.pedigree_number) as a_stn,
                  CONCAT(m.uln_country_code, m.uln_number) as m_uln, CONCAT(m.pedigree_country_code, m.pedigree_number) as m_stn,
                  CONCAT(f.uln_country_code, f.uln_number) as f_uln, CONCAT(f.pedigree_country_code, f.pedigree_number) as f_stn,
    a.gender,
                  a.animal_order_number as a_animal_order_number, f.animal_order_number as f_animal_order_number, m.animal_order_number as m_animal_order_number,
                  DATE(a.date_of_birth) as a_date_of_birth, DATE(m.date_of_birth) as m_date_of_birth, DATE(f.date_of_birth) as f_date_of_birth,
                  a.breed_code as a_breed_code, m.breed_code as m_breed_code, f.breed_code as f_breed_code,
                  a.scrapie_genotype as a_scrapie_genotype, m.scrapie_genotype as m_scrapie_genotype, f.scrapie_genotype as f_scrapie_genotype,
                  a.breed_code as a_breed_code, m.breed_code as m_breed_code, f.breed_code as f_breed_code,
                  ac.dutch_breed_status as a_dutch_breed_status, mc.dutch_breed_status as m_dutch_breed_status, fc.dutch_breed_status as f_dutch_breed_status,
                  ac.n_ling as a_n_ling, mc.n_ling as m_n_ling, fc.n_ling as f_n_ling,
                  a.predicate as a_predicate_value, m.predicate as m_predicate_value, f.predicate as f_predicate_value,
                  a.predicate_score as a_predicate_score, m.predicate_score as m_predicate_score, f.predicate_score as f_predicate_score,
                  ac.muscularity as a_muscularity, mc.muscularity as m_muscularity, fc.muscularity as f_muscularity,
                  ac.general_appearance as a_general_appearance, mc.general_appearance as m_general_appearance, fc.general_appearance as f_general_appearance,

                  ac.production_age as a_production_age, mc.production_age as m_production_age, fc.production_age as f_production_age,
                  ac.litter_count as a_litter_count, mc.litter_count as m_litter_count, fc.litter_count as f_litter_count,
                  ac.total_offspring_count as a_total_offspring_count, mc.total_offspring_count as m_total_offspring_count, fc.total_offspring_count as f_total_offspring_count,
                  ac.born_alive_offspring_count as a_born_alive_offspring_count, mc.born_alive_offspring_count as m_born_alive_offspring_count, fc.born_alive_offspring_count as f_born_alive_offspring_count,
                  ac.gave_birth_as_one_year_old as a_gave_birth_as_one_year_old, mc.gave_birth_as_one_year_old as m_gave_birth_as_one_year_old, fc.gave_birth_as_one_year_old as f_gave_birth_as_one_year_old,

                  ab.total_born as a_breed_value_litter_size_value, mb.total_born as m_breed_value_litter_size_value, fb.total_born as f_breed_value_litter_size_value,
                  ab.total_born_accuracy as a_breed_value_litter_size_accuracy, mb.total_born_accuracy as m_breed_value_litter_size_accuracy, fb.total_born_accuracy as f_breed_value_litter_size_accuracy,

                  ab.growth as a_breed_value_growth_value, mb.growth as m_breed_value_growth_value, fb.growth as f_breed_value_growth_value,
                  ab.growth_accuracy as a_breed_value_growth_accuracy, mb.growth_accuracy as m_breed_value_growth_accuracy, fb.growth_accuracy as f_breed_value_growth_accuracy,

                  ab.muscle_thickness as a_breed_value_muscle_thickness_value, mb.muscle_thickness as m_breed_value_muscle_thickness_value, fb.muscle_thickness as f_breed_value_muscle_thickness_value,
                  ab.muscle_thickness_accuracy as a_breed_value_muscle_thickness_accuracy, mb.muscle_thickness_accuracy as m_breed_value_muscle_thickness_accuracy, fb.muscle_thickness_accuracy as f_breed_value_muscle_thickness_accuracy,

                  ab.fat_thickness3 as a_breed_value_fat_value, mb.fat_thickness3 as m_breed_value_fat_value, fb.fat_thickness3 as f_breed_value_fat_value,
                  ab.fat_thickness3accuracy as a_breed_value_fat_accuracy, mb.fat_thickness3accuracy as m_breed_value_fat_accuracy, fb.fat_thickness3accuracy as f_breed_value_fat_accuracy,

                  ab.lamb_meat_index as a_lamb_meat_index_value, mb.lamb_meat_index as m_lamb_meat_index_value, fb.lamb_meat_index as f_lamb_meat_index_value,
                  ab.lamb_meat_accuracy as a_lamb_meat_accuracy, mb.lamb_meat_accuracy as m_lamb_meat_accuracy, fb.lamb_meat_accuracy as f_lamb_meat_accuracy
  FROM animal a
    LEFT JOIN animal m ON a.parent_mother_id = m.id
    LEFT JOIN animal f ON a.parent_father_id = f.id
    LEFT JOIN animal_cache ac ON a.id = ac.animal_id
    LEFT JOIN animal_cache mc ON m.id = mc.animal_id
    LEFT JOIN animal_cache fc ON f.id = fc.animal_id
    LEFT JOIN result_table_breed_grades ab ON a.id = ab.animal_id
    LEFT JOIN result_table_breed_grades mb ON m.id = mb.animal_id
    LEFT JOIN result_table_breed_grades fb ON f.id = fb.animal_id
  WHERE a.is_alive = true AND a.location_id = locationId
  ORDER BY a.animal_order_number ASC";

        $parameters = [
          'locationId' => 'INTEGER',
        ];

        self::createOrUpdateProcedureBase($conn, self::GET_LIVESTOCK_REPORT, $sql, $parameters);
    }

}