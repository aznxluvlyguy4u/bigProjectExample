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
                WHERE status = 'REVOKED' AND mate_id NOTNULL";
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
                                                     AND l.status = 'COMPLETED' AND l.is_abortion = FALSE AND l.is_pseudo_pregnancy = FALSE
                                                     ".$litterIdFilter."
                                               UNION
                                               -- 2. Find the children from others for which the mother is a surrogate
                                               SELECT child.id as suckling, l.id as litter_id FROM litter l
                                                 INNER JOIN animal child ON l.animal_mother_id = child.surrogate_id
                                               WHERE ABS(DATE(child.date_of_birth) - DATE(l.litter_date)) <= 14
                                                     AND l.status = 'COMPLETED' AND l.is_abortion = FALSE AND l.is_pseudo_pregnancy = FALSE
                                                     ".$litterIdFilter."
                                             ) AS suckers_calculation_part_1
                                        GROUP BY litter_id
                                        UNION
                                        -- 3. Make sure the litters with born_alive_count = 0 are included
                                        SELECT l.id as litter_id, 0 as calculated_suckle_count FROM litter l
                                        WHERE born_alive_count = 0
                                              AND l.status = 'COMPLETED' AND l.is_abortion = FALSE AND l.is_pseudo_pregnancy = FALSE
                                              ".$litterIdFilter."
                                        UNION
                                        -- 4. Make sure the litters where all children have surrogates are included
                                        SELECT l.id as litter_id, 0 as calculated_suckle_count FROM litter l
                                          INNER JOIN (
                                                       SELECT l.id, COUNT(child.id) - SUM(CAST(child.surrogate_id NOTNULL AS INTEGER)) = 0 AS all_children_have_surrogates
                                                       FROM litter l
                                                         INNER JOIN animal child ON child.litter_id = l.id
                                                       WHERE l.born_alive_count <> 0 AND l.suckle_count ISNULL
                                                             AND l.status = 'COMPLETED' AND l.is_abortion = FALSE AND l.is_pseudo_pregnancy = FALSE
                                                             ".$litterIdFilter."
                                                       GROUP BY l.id
                                                     )g ON g.id = l.id
                                        WHERE g.all_children_have_surrogates
                                      ) AS suckers_calculation
                                 GROUP BY litter_id
                               )suckers ON suckers.litter_id = l.id
                  WHERE (l.suckle_count <> suckers.calculated_suckle_count
                         OR l.suckle_count ISNULL AND suckers.calculated_suckle_count NOTNULL)
                        AND l.status = 'COMPLETED' AND l.is_abortion = FALSE AND l.is_pseudo_pregnancy = FALSE
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
                WHERE status = 'REVOKED' AND (suckle_count NOTNULL OR suckle_count_update_date NOTNULL)";
        return SqlUtil::updateWithCount($conn, $sql);
    }


    /**
     * @param Connection $conn
     * @param null $eweId
     * @return int
     */
    public static function updateLitterOrdinals(Connection $conn, $eweId = null)
    {
        $animalMotherIdFilter = ctype_digit($eweId) || is_int($eweId) ? ' AND animal_mother_id = '.$eweId.' ' : '';
        $sql = "UPDATE litter SET litter_ordinal = v.calc_litter_ordinal
                FROM (
                  SELECT l.id as litter_id,
                    DENSE_RANK() OVER (PARTITION BY animal_mother_id ORDER BY litter_date ASC) AS calc_litter_ordinal
                  FROM litter l
                  WHERE animal_mother_id IN (
                    SELECT animal_mother_id FROM litter
                    WHERE litter_ordinal ISNULL AND
                          (status = 'COMPLETE' OR status = 'IMPORTED')
                    GROUP BY animal_mother_id
                  ) AND (status = 'COMPLETE' OR status = 'IMPORTED') ".$animalMotherIdFilter."
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
                WHERE (status = 'REVOKED' OR status = 'INCOMPLETE') AND litter_ordinal NOTNULL";
        return SqlUtil::updateWithCount($conn, $sql);
    }

}