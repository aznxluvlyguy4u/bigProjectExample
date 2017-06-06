<?php


namespace AppBundle\Util;


use AppBundle\Entity\DeclareBirthRepository;
use AppBundle\Enumerator\RequestStateType;
use Doctrine\DBAL\Connection;

class LitterUtil
{
    const MIN_YEAR = 2016;

    /**
     * @param Connection $conn
     * @param boolean $regenerate
     * @param null $litterId
     * @return int
     */
    public static function matchMatchingMates(Connection $conn, $regenerate = false, $litterId = null)
    {
        $filter = self::getMatchingMatesFilter($regenerate, $litterId);

        $sql = "UPDATE litter SET mate_id = v.mate_id
                FROM (
                       SELECT l.id as litter_id, m.id as mate_id FROM litter l
                         INNER JOIN mate m ON l.animal_mother_id = m.stud_ewe_id AND l.animal_father_id = m.stud_ram_id
                         INNER JOIN declare_nsfo_base bl ON bl.id = l.id
                         INNER JOIN declare_nsfo_base bm ON bm.id = m.id
                         INNER JOIN (
                                      SELECT l.id, animal_mother_id, animal_father_id, MAX(m.end_date) as max_end_date FROM litter l
                                        INNER JOIN mate m ON l.animal_mother_id = m.stud_ewe_id AND l.animal_father_id = m.stud_ram_id
                                        INNER JOIN declare_nsfo_base bl ON bl.id = l.id
                                        INNER JOIN declare_nsfo_base bm ON bm.id = m.id
                                       ".$filter."
                                      GROUP BY l.id, animal_mother_id, animal_father_id
                                    )g ON g.max_end_date = m.end_date AND l.animal_mother_id = g.animal_mother_id AND l.animal_father_id = g.animal_father_id
                       ".$filter."
                ) as v(litter_id, mate_id) WHERE id = v.litter_id
                ";
        return SqlUtil::updateWithCount($conn, $sql);
    }


    /**
     * @param Connection $conn
     * @return int
     */
    public static function removeMatesFromRevokedLitters(Connection $conn)
    {
        $sql = "UPDATE litter SET mate_id = NULL
                WHERE status = '".RequestStateType::REVOKED."' AND mate_id NOTNULL";
        return SqlUtil::updateWithCount($conn, $sql);
    }


    /**
     * @param Connection $conn
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function countToBeMatchedLitters(Connection $conn)
    {
        $sql = "SELECT COUNT(*) FROM (
                  SELECT l.id as count FROM litter l
                     INNER JOIN mate m ON l.animal_mother_id = m.stud_ewe_id AND l.animal_father_id = m.stud_ram_id
                     INNER JOIN declare_nsfo_base bl ON bl.id = l.id
                     INNER JOIN declare_nsfo_base bm ON bm.id = m.id
                   ".self::getMatchingMatesFilter(false)."
                   GROUP BY l.id, l.animal_mother_id, l.animal_father_id
                ) AS a";
        return $conn->query($sql)->fetch()['count'];
    }


    /**
     * @param bool $regenerate
     * @param int $litterId
     * @return string
     */
    private static function getMatchingMatesFilter($regenerate = false, $litterId = null)
    {
        $filterByLitterId = is_int($litterId) || ctype_digit($litterId) ? ' AND l.id = '.$litterId : '';
        $regenerateFilter = $regenerate ? '' : ' AND l.mate_id ISNULL ';

        return "WHERE DATE_PART('year', l.litter_date) >= ".self::MIN_YEAR."
                AND ".DeclareBirthRepository::MATING_CANDIDATE_START_OFFSET." <= l.litter_date::date - m.start_date::date
                AND l.litter_date::date - m.end_date::date <= ".DeclareBirthRepository::MATING_CANDIDATE_END_OFFSET."
                AND bm.is_overwritten_version = FALSE
                AND (bm.request_state = '".RequestStateType::FINISHED."' OR bm.request_state = '".RequestStateType::FINISHED_WITH_WARNING."')
                AND bl.is_overwritten_version = FALSE
                AND (bl.request_state = '".RequestStateType::FINISHED."' OR bl.request_state = '".RequestStateType::FINISHED_WITH_WARNING."')
                AND (m.is_approved_by_third_party ISNULL OR m.is_approved_by_third_party = TRUE)
                AND l.status <> '".RequestStateType::REVOKED."' ".$filterByLitterId.$regenerateFilter;
    }


    /**
     * Ewes with abortions and pseudopregnancies cannot give milk, while an ewe with only stillborns can.
     * Because the imported data from VSM has no registered surrogate mothers, the suckleCount will only be calculated
     * for litters registered in this current NSFO system.
     * 
     * @param Connection $conn
     * @param null $litterId
     * @return int
     */
    public static function updateSuckleCount(Connection $conn, $litterId = null)
    {
        $litterIdFilter = ctype_digit($litterId) || is_int($litterId) ? ' AND l.id = '.$litterId.' ' : '';

        $sql = "UPDATE litter SET suckle_count_update_date = NOW(), suckle_count = v.calculated_suckle_count
                FROM(
                  SELECT l.id, calculated_suckle_count, l.suckle_count FROM litter l
                    INNER JOIN (
                                 SELECT litter_id, SUM(calculated_suckle_count) as calculated_suckle_count
                                 FROM (
                                        SELECT litter_id, COUNT(litter_id) as calculated_suckle_count
                                        FROM (
                                               -- 1. Find the children in own litter without a surrogate,
                                               -- in this part min(child_count without surrogate) = 1
                                               SELECT child.id as suckling, l.id as litter_id
                                               FROM litter l
                                                 INNER JOIN animal child ON l.id = child.litter_id
                                               WHERE child.surrogate_id ISNULL
                                                     AND l.status = '".RequestStateType::COMPLETED."' AND l.is_abortion = FALSE AND l.is_pseudo_pregnancy = FALSE
                                                     ".$litterIdFilter."
                                               UNION
                                               -- 2. Find the children from others for which the mother is a surrogate
                                               SELECT child.id as suckling, l.id as litter_id FROM litter l
                                                 INNER JOIN animal child ON l.animal_mother_id = child.surrogate_id
                                               WHERE ABS(DATE(child.date_of_birth) - DATE(l.litter_date)) <= 14
                                                     AND l.status = '".RequestStateType::COMPLETED."' AND l.is_abortion = FALSE AND l.is_pseudo_pregnancy = FALSE
                                                     ".$litterIdFilter."
                                             ) AS suckers_calculation_part_1
                                        GROUP BY litter_id
                                        UNION
                                        -- 3. Make sure the litters with born_alive_count = 0 are included
                                        SELECT l.id as litter_id, 0 as calculated_suckle_count FROM litter l
                                        WHERE born_alive_count = 0
                                              AND l.status = '".RequestStateType::COMPLETED."' AND l.is_abortion = FALSE AND l.is_pseudo_pregnancy = FALSE
                                              ".$litterIdFilter."
                                        UNION
                                        -- 4. Make sure the litters where all children have surrogates are included
                                        SELECT l.id as litter_id, 0 as calculated_suckle_count FROM litter l
                                          INNER JOIN (
                                                       SELECT l.id, COUNT(child.id) - SUM(CAST(child.surrogate_id NOTNULL AS INTEGER)) = 0 AS all_children_have_surrogates
                                                       FROM litter l
                                                         INNER JOIN animal child ON child.litter_id = l.id
                                                       WHERE l.born_alive_count <> 0 AND l.suckle_count ISNULL
                                                             AND l.status = '".RequestStateType::COMPLETED."' AND l.is_abortion = FALSE AND l.is_pseudo_pregnancy = FALSE
                                                             ".$litterIdFilter."
                                                       GROUP BY l.id
                                                     )g ON g.id = l.id
                                        WHERE g.all_children_have_surrogates
                                      ) AS suckers_calculation
                                 GROUP BY litter_id
                               )suckers ON suckers.litter_id = l.id
                  WHERE (l.suckle_count <> suckers.calculated_suckle_count
                         OR l.suckle_count ISNULL AND suckers.calculated_suckle_count NOTNULL)
                        AND l.status = '".RequestStateType::COMPLETED."' AND l.is_abortion = FALSE AND l.is_pseudo_pregnancy = FALSE
                        ".$litterIdFilter."
                ) AS v(litter_id, calculated_suckle_count) WHERE id = litter_id";
        return SqlUtil::updateWithCount($conn, $sql);
    }


    /**
     * @param Connection $conn
     * @return int
     */
    public static function removeSuckleCountFromRevokedLitters(Connection $conn)
    {
        $sql = "UPDATE litter SET suckle_count = NULL, suckle_count_update_date = NULL
                WHERE status = '".RequestStateType::REVOKED."' AND (suckle_count NOTNULL OR suckle_count_update_date NOTNULL)";
        return SqlUtil::updateWithCount($conn, $sql);
    }


    /**
     * @param Connection $conn
     * @param int|string $litterId
     * @return int
     */
    public static function updateLitterOrdinals(Connection $conn, $litterId = null)
    {
        $animalMotherIdFilter = ctype_digit($litterId) || is_int($litterId) ?
            " AND l.animal_mother_id IN (\n" +
            "      SELECT animal_mother_id FROM litter WHERE id = ".$litterId."\n" +
            "    ) " : '';

        $sql = "UPDATE litter SET litter_ordinal = v.calc_litter_ordinal
                FROM (
                  SELECT l.id as litter_id,
                    DENSE_RANK() OVER (PARTITION BY animal_mother_id ORDER BY litter_date ASC) AS calc_litter_ordinal
                  FROM litter l
                  WHERE animal_mother_id IN (
                    SELECT animal_mother_id FROM litter
                    WHERE litter_ordinal ISNULL AND
                          (status = '".RequestStateType::COMPLETED."' OR status = '".RequestStateType::IMPORTED."')
                    GROUP BY animal_mother_id
                  ) AND (status = '".RequestStateType::COMPLETED."' OR status = '".RequestStateType::IMPORTED."') ".$animalMotherIdFilter."
                  ORDER BY animal_mother_id ASC, litter_date ASC
                ) AS v(litter_id, calc_litter_ordinal)
                WHERE litter.id = litter_id
                  AND (litter.litter_ordinal ISNULL OR litter.litter_ordinal <> v.calc_litter_ordinal)";
        return SqlUtil::updateWithCount($conn, $sql);
    }


    /**
     * @param Connection $conn
     * @return int
     */
    public static function removeLitterOrdinalFromRevokedLitters(Connection $conn)
    {
        $sql = "UPDATE litter SET litter_ordinal = NULL
                WHERE (status = '".RequestStateType::REVOKED."' OR status = '".RequestStateType::INCOMPLETE."') AND litter_ordinal NOTNULL";
        return SqlUtil::updateWithCount($conn, $sql);
    }


    /**
     * @param Connection $conn
     * @param null|int $litterId
     * @return int
     */
    public static function updateGestationPeriods(Connection $conn, $litterId = null)
    {
        $litterIdFilter = ctype_digit($litterId) || is_int($litterId) ? ' AND l.id = '.$litterId.' ' : '';

        $sql = "UPDATE litter SET gestation_period = v.calc_gestation_period
                FROM (
                  SELECT l.id as litter_id, DATE(litter_date)-DATE(m.start_date) as calc_gestation_period
                  FROM litter l
                    INNER JOIN mate m ON l.mate_id = m.id
                  WHERE DATE(m.start_date) = DATE(m.end_date)
                        AND m.start_date NOTNULL
                        AND (gestation_period ISNULL OR gestation_period <> (DATE(litter_date)-DATE(m.start_date)))
                        AND l.status = '".RequestStateType::COMPLETED."' --NOTE IMPORTED litters are ignored
                        ".$litterIdFilter."
                ) AS v(litter_id, calc_gestation_period) WHERE litter.id = v.litter_id";
        $updateCountNewLitterMateMatch = SqlUtil::updateWithCount($conn, $sql);


        $litterIdFilter = ctype_digit($litterId) || is_int($litterId) ? ' AND litter.id = '.$litterId.' ' : '';

        $sql = "UPDATE litter SET gestation_period = NULL
                WHERE gestation_period NOTNULL AND
                      (mate_id ISNULL OR status = '".RequestStateType::INCOMPLETE."' OR status = '".RequestStateType::REVOKED."'
                      OR
                      id NOT IN (
                  SELECT l.id as litter_id
                  FROM litter l
                    INNER JOIN mate m ON l.mate_id = m.id
                    INNER JOIN declare_nsfo_base bm ON m.id = bm.id
                  WHERE DATE(m.start_date) = DATE(m.end_date)
                        AND l.status = '".RequestStateType::COMPLETED."' --NOTE IMPORTED litters are ignored
                        AND bm.request_state = '".RequestStateType::FINISHED."' OR bm.request_state = '".RequestStateType::FINISHED_WITH_WARNING."'
                )) ".$litterIdFilter;
        $updateCountRevokedLitterOrMate = SqlUtil::updateWithCount($conn, $sql);

        return $updateCountNewLitterMateMatch + $updateCountRevokedLitterOrMate;
    }


    /**
     * NOTE! Update litterOrdinals first!
     * 
     * @param Connection $conn
     * @param null $litterId
     * @return int
     */
    public static function updateBirthInterVal(Connection $conn, $litterId = null)
    {
        $litterIdFilter = ctype_digit($litterId) || is_int($litterId) ? ' AND l.id = '.$litterId.' ' : '';
        
        $sql = "UPDATE litter SET birth_interval = v.calc_birth_interval
                FROM (
                       SELECT l.id as litter_id, DATE(l.litter_date)-DATE(previous_litter.litter_date) as calc_birth_interval
                       FROM litter l
                         INNER JOIN litter previous_litter ON previous_litter.animal_mother_id = l.animal_mother_id AND previous_litter.litter_ordinal = l.litter_ordinal-1
                       WHERE l.litter_ordinal > 1
                             AND (l.status = '".RequestStateType::COMPLETED."' OR l.status = '".RequestStateType::IMPORTED."')
                             AND (previous_litter.status = '".RequestStateType::COMPLETED."' OR previous_litter.status = '".RequestStateType::IMPORTED."')
                             AND (l.birth_interval ISNULL OR l.birth_interval <> (DATE(l.litter_date)-DATE(previous_litter.litter_date)))
                             ".$litterIdFilter."
                     ) AS v(litter_id, calc_birth_interval) WHERE litter.id = v.litter_id";
        $updateIncongruentBirthIntervals = SqlUtil::updateWithCount($conn, $sql);


        $litterIdFilter = ctype_digit($litterId) || is_int($litterId) ? ' AND litter.id = '.$litterId.' ' : '';

        $sql = "UPDATE litter SET birth_interval = NULL
                WHERE (litter_ordinal <= 1 OR litter_ordinal ISNULL) AND birth_interval NOTNULL ".$litterIdFilter;
        $updateRevokedBirthIntervals = SqlUtil::updateWithCount($conn, $sql);
        
        return $updateIncongruentBirthIntervals + $updateRevokedBirthIntervals;
    }


    /**
     * @param Connection $conn
     * @return int
     */
    public static function deleteDuplicateLittersWithoutBornAlive(Connection $conn)
    {
        $sql = "SELECT
                  l.id as litter_id,
                --   l.animal_mother_id, l.animal_father_id, DATE(l.litter_date) as worpdatum, b.log_date,
                --   l.stillborn_count, l.born_alive_count, l.is_abortion, l.is_pseudo_pregnancy as gust, l.mate_id, l.status,
                --   b.action_by_id, CONCAT(p.first_name,' ',p.last_name) as action_by_fullname, b.ubn,
                  s.id as stillborn_id
                FROM litter l
                  INNER JOIN declare_nsfo_base b ON l.id = b.id
                  LEFT JOIN person p ON b.action_by_id = p.id
                  LEFT JOIN stillborn s ON s.litter_id = l.id
                  LEFT JOIN animal mom ON mom.id = animal_mother_id
                  LEFT JOIN animal dad ON dad.id = animal_father_id
                  INNER JOIN (
                               SELECT animal_father_id, animal_mother_id, litter_date, MIN(log_date) as min_log_date
                               FROM litter l
                                 INNER JOIN declare_nsfo_base b ON l.id = b.id
                               WHERE status = 'COMPLETED' AND request_state = 'FINISHED' AND is_overwritten_version = FALSE
                               AND born_alive_count = 0
                               GROUP BY animal_mother_id, animal_father_id, litter_date
                                     , stillborn_count
                               HAVING COUNT (*) > 1
                             )g ON g.animal_father_id = l.animal_father_id AND g.animal_mother_id = l.animal_mother_id AND g.litter_date = l.litter_date
                AND b.log_date <> min_log_date
                ORDER BY l.animal_mother_id, l.animal_father_id, l.litter_date, b.log_date";
        $results = $conn->query($sql)->fetchAll();

        $litterIds = [];
        $stillbornIds = [];
        foreach ($results as $result) {
            $litterIds[] = $result['litter_id'];
            $stillbornId = $result['stillborn_id'];
            if(is_int($stillbornId)) {
                $stillbornIds[] = $result['stillborn_id'];
            }
        }

        $sql = "DELETE FROM stillborn WHERE id IN (".implode(', ', $stillbornIds).")";
        $stillbornsDeleted = SqlUtil::updateWithCount($conn, $sql);

        $sql = "DELETE FROM litter WHERE id IN (".implode(', ', $litterIds).")";
        $littersDeleted = SqlUtil::updateWithCount($conn, $sql);

        return $littersDeleted;
    }
}