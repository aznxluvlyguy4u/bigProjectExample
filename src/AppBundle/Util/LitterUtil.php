<?php


namespace AppBundle\Util;


use AppBundle\Entity\DeclareBirthRepository;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Enumerator\ExteriorKind;
use AppBundle\Enumerator\PredicateType;
use AppBundle\Enumerator\RequestStateType;
use Doctrine\DBAL\Connection;
use Monolog\Logger;

class LitterUtil
{
    const MIN_YEAR = 2016;


    /**
     * @param Connection $conn
     * @param Logger $logger
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function validateDuplicateLitters(Connection $conn, Logger $logger): bool {
        $sqlDuplicateLitterCheck = "SELECT
            l.id
        FROM litter l
        INNER JOIN (
            SELECT
                animal_mother_id,
                animal_father_id,
                litter_date
            FROM litter l
            WHERE 
                  l.status <> 'REVOKED' AND 
                  DATE_PART('year', l.litter_date) >= ".self::MIN_YEAR."
            GROUP BY animal_father_id, animal_mother_id, litter_date HAVING COUNT(*) > 1
            )g ON g.litter_date = l.litter_date AND g.animal_father_id = l.animal_father_id AND g.animal_mother_id = l.animal_mother_id
        INNER JOIN declare_nsfo_base dnb on l.id = dnb.id;";
        $ids = SqlUtil::getSingleValueGroupedSqlResults('id',
            $conn->query($sqlDuplicateLitterCheck)->fetchAll(),
            true,
            true
        );

        if (empty($ids)) {
            $logger->notice("No duplicate litters with litterDate after ".self::MIN_YEAR." are found.");
            return true;
        }

        $errorHeader = "=== DUPLICATE LITTERS FOUND ===";
        $logger->error($errorHeader);
        $logger->error("Duplicate litterIds: ".implode(",", $ids));
        $logger->error($errorHeader);
        $logger->error("Fix duplicate litters and animals first");
        return false;
    }


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
                                      SELECT l.id, l.animal_mother_id, l.animal_father_id, MAX(m.end_date) as max_end_date,
                                             min(min_litter_date) as double_matched_min_litter_date
                                      FROM litter l
                                        INNER JOIN mate m ON l.animal_mother_id = m.stud_ewe_id AND l.animal_father_id = m.stud_ram_id
                                        INNER JOIN declare_nsfo_base bl ON bl.id = l.id
                                        INNER JOIN declare_nsfo_base bm ON bm.id = m.id
                                        LEFT JOIN (
                                          --check if a single mate is matched with more than one litter
                                          --if that is the case, link the mate to the first litter
                                          SELECT animal_mother_id, animal_father_id, m.id as mate_id,
                                                 COUNT(*) as matched_litter_mates, MIN(l.litter_date) as min_litter_date
                                          FROM litter l
                                            INNER JOIN mate m ON l.animal_mother_id = m.stud_ewe_id AND l.animal_father_id = m.stud_ram_id
                                            INNER JOIN declare_nsfo_base bl ON bl.id = l.id
                                            INNER JOIN declare_nsfo_base bm ON bm.id = m.id
                                          ".self::getMatchingMatesFilter(true, null)."
                                          GROUP BY animal_mother_id, animal_father_id, m.id HAVING COUNT(*) > 1
                                          ) AS double_matched_mates ON double_matched_mates.mate_id = m.id  
                                       ".self::getMatchingMatesFilter($regenerate, $litterId, true)."
                                      GROUP BY l.id, l.animal_mother_id, l.animal_father_id
                                    )g ON g.max_end_date = m.end_date AND l.animal_mother_id = g.animal_mother_id AND l.animal_father_id = g.animal_father_id
                                    AND (l.litter_date = double_matched_min_litter_date OR double_matched_min_litter_date ISNULL)
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
     * @param bool $groupDoubleMatchedMates
     * @return string
     */
    private static function getMatchingMatesFilter($regenerate = false, $litterId = null, $groupDoubleMatchedMates = false)
    {
        $doubleMatchedMatesFilter = '';
        if ($groupDoubleMatchedMates) {
            $doubleMatchedMatesFilter =
                ' AND (double_matched_mates.mate_id ISNULL
                        OR double_matched_mates.min_litter_date = l.litter_date
                      ) ';
        }

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
                AND l.status <> '".RequestStateType::REVOKED."' ".$filterByLitterId.$regenerateFilter.$doubleMatchedMatesFilter;
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


    private static function getLitterAnimalMotherIdFilter($litterId = null, string $litterAlias = 'l'): string
    {
        return ctype_digit($litterId) || is_int($litterId) ?
            " AND $litterAlias.animal_mother_id IN (" .
            "      SELECT animal_mother_id FROM litter WHERE id = ".$litterId." " .
            "    ) " : '';
    }


    /**
     * @param Connection $conn
     * @return int
     */
    public static function updateAllLitterOrdinals(Connection $conn)
    {
        return self::updateLitterOrdinalsBase($conn);
    }


    /**
     * @param Connection $conn
     * @param array $motherIds
     * @return int
     */
    public static function updateLitterOrdinalsByMotherIds(Connection $conn, array $motherIds = [])
    {
        if (empty($motherIds)) {
            return 0;
        }

        $litterAlias = 'l';
        $motherIdsJoined = SqlUtil::getIdsFilterListString($motherIds);

        $animalMotherIdFilter = " AND $litterAlias.animal_mother_id IN (".$motherIdsJoined.") ";

        return self::updateLitterOrdinalsBase($conn, $animalMotherIdFilter);
    }


    /**
     * @param Connection $conn
     * @param string $animalMotherIdFilter
     * @return int
     */
    private static function updateLitterOrdinalsBase(Connection $conn, string $animalMotherIdFilter = '')
    {
        $sqlGlobalLitter = "UPDATE litter SET litter_ordinal = v.calc_litter_ordinal
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
        $globalLitterUpdateCount = SqlUtil::updateWithCount($conn, $sqlGlobalLitter);

        $sqlStandardLitter = "UPDATE litter SET standard_litter_ordinal = v.calc_standard_litter_ordinal
                FROM (
                  SELECT l.id as litter_id,
                    DENSE_RANK() OVER (PARTITION BY animal_mother_id ORDER BY litter_date ASC) AS calc_standard_litter_ordinal
                  FROM litter l
                  WHERE is_pseudo_pregnancy = FALSE AND is_abortion = FALSE AND
                    animal_mother_id
                    IN (
                        SELECT animal_mother_id FROM litter
                        WHERE standard_litter_ordinal ISNULL AND
                              (status = '".RequestStateType::COMPLETED."' OR status = '".RequestStateType::IMPORTED."')
                        GROUP BY animal_mother_id
                      ) AND (status = '".RequestStateType::COMPLETED."' OR status = '".RequestStateType::IMPORTED."') ".$animalMotherIdFilter."
                  ORDER BY animal_mother_id ASC, litter_date ASC
                ) AS v(litter_id, calc_standard_litter_ordinal)
                WHERE litter.id = litter_id
                  AND (litter.standard_litter_ordinal ISNULL OR litter.standard_litter_ordinal <> v.calc_standard_litter_ordinal)";
        $standardLitterUpdateCount = SqlUtil::updateWithCount($conn, $sqlStandardLitter);

        return $globalLitterUpdateCount + $standardLitterUpdateCount;
    }


    /**
     * @param Connection $conn
     * @return int
     */
    public static function removeLitterOrdinalFromRevokedLitters(Connection $conn)
    {
        $sql = "UPDATE litter SET litter_ordinal = NULL, standard_litter_ordinal = NULL
                WHERE (status = '".RequestStateType::REVOKED."' OR status = '".RequestStateType::INCOMPLETE."') AND litter_ordinal NOTNULL";
        return SqlUtil::updateWithCount($conn, $sql);
    }


    public static function updateCumulativeBornAliveCount(Connection $conn, $litterId = null)
    {
        $animalMotherIdFilter = self::getLitterAnimalMotherIdFilter($litterId);

        $activeRequestStateTypes = SqlUtil::activeRequestStateTypesForLittersJoinedList();

        $sql = "UPDATE litter SET cumulative_born_alive_count = v.new_cumulative_born_alive_count
FROM (
         SELECT
             l.id,
             cumulative_born_alive_count,
             SUM(l.born_alive_count) OVER (PARTITION BY animal_mother_id ORDER BY standard_litter_ordinal) as count
         FROM litter l
                  INNER JOIN declare_nsfo_base dnb on l.id = dnb.id
         WHERE dnb.request_state IN ($activeRequestStateTypes)
           $animalMotherIdFilter
           AND standard_litter_ordinal NOTNULL
         ORDER BY animal_mother_id, standard_litter_ordinal
) AS v (litter_id, current_cumulative_born_alive_count, new_cumulative_born_alive_count)
WHERE litter.id = v.litter_id AND (
    v.current_cumulative_born_alive_count ISNULL OR
    v.current_cumulative_born_alive_count <> new_cumulative_born_alive_count
    )";
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
     * @param array $motherIds
     * @return int
     */
    public static function updateBirthInterValByMotherIds(Connection $conn, array $motherIds = [])
    {
        if (empty($motherIds)) {
            return 0;
        }

        $motherIdsJoined = SqlUtil::getIdsFilterListString($motherIds);

        $litterIdFilter = " AND l.id IN (
            SELECT id FROM litter WHERE animal_mother_id IN ($motherIdsJoined) GROUP BY id
        ) ";

        return self::updateBirthInterValBase($conn, $litterIdFilter);
    }


    /**
     * NOTE! Update litterOrdinals first!
     *
     * @param Connection $conn
     * @return int
     */
    public static function updateAllBirthInterVal(Connection $conn)
    {
        return self::updateBirthInterValBase($conn, '');
    }


    /**
     * NOTE! Update litterOrdinals first!
     *
     * @param Connection $conn
     * @param string $litterIdFilter
     * @return int
     */
    private static function updateBirthInterValBase(Connection $conn, string $litterIdFilter = '')
    {
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


        $sql = "UPDATE litter l SET birth_interval = NULL
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
        $littersDeleted = 0;

        //1. First delete duplicate litters with exactly the same values and litterDate
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

        $littersDeleted += self::deleteStillBornsAndLitters($conn, $results);

        //2. Delete duplicate litters with not exactly the same litterDate
        /* NOTE! It is not unusual for 2 litters to happen in the same year from the same mother.
            So make sure they are also in the same month, just to be safe.
        */
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
                               SELECT animal_father_id, animal_mother_id, stillborn_count, MIN(log_date) as min_log_date
                               FROM litter l
                                 INNER JOIN declare_nsfo_base b ON l.id = b.id
                               WHERE status = 'COMPLETED' AND request_state = 'FINISHED' AND is_overwritten_version = FALSE
                               AND born_alive_count = 0
                               GROUP BY animal_mother_id, animal_father_id, stillborn_count, 
                               DATE_PART('year', litter_date), DATE_PART('month', litter_date)
                               HAVING COUNT (*) > 1
                             )g ON g.animal_father_id = l.animal_father_id AND g.animal_mother_id = l.animal_mother_id AND g.stillborn_count = l.stillborn_count
                AND b.log_date <> min_log_date
                ORDER BY l.animal_mother_id, l.animal_father_id, l.stillborn_count, b.log_date";
        $results = $conn->query($sql)->fetchAll();

        $littersDeleted += self::deleteStillBornsAndLitters($conn, $results);

        return $littersDeleted;
    }


    /**
     * @param Connection $conn
     * @param array $results
     * @return int
     */
    private static function deleteStillBornsAndLitters(Connection $conn, array $results)
    {
        $litterIds = [];
        $stillbornIds = [];
        foreach ($results as $result) {
            $litterIds[] = $result['litter_id'];
            $stillbornId = $result['stillborn_id'];
            if(is_int($stillbornId)) {
                $stillbornIds[] = $result['stillborn_id'];
            }
        }

        $stillbornsDeleted = 0;
        if(count($stillbornIds) > 0) {
            $sql = "DELETE FROM stillborn WHERE id IN (".implode(', ', $stillbornIds).")";
            $stillbornsDeleted += SqlUtil::updateWithCount($conn, $sql);
        }

        $littersDeleted = 0;
        if(count($litterIds) > 0) {
            $sql = "DELETE FROM declare_nsfo_base WHERE id IN (".implode(', ', $litterIds).")";
            $littersDeleted += SqlUtil::updateWithCount($conn, $sql);
        }

        return $littersDeleted;
    }

    public static function updateLitterOffspringExteriorAndStarEweValues(Connection $conn, $litterId = null, ?Logger $logger = null): int
    {
        $updateCount = self::updateLitterOffspringExteriorValues($conn, $litterId, $logger);
        return $updateCount + self::updateLitterStarEweBasePoints($conn, $litterId, $logger);
    }

    private static function updateLitterOffspringExteriorValues(Connection $conn, $litterId = null, ?Logger $logger = null): int
    {
        if ($logger) {
            $updateType = !empty($litterId) && is_int($litterId) ? "litter with id $litterId" : "ALL litters";
            $logger->notice("Update $updateType offspring exterior values ...");
        }

        $litterIdFilter = !empty($litterId) && is_int($litterId) ? " AND litter.id = $litterId " : '';
        $subLitterIdFilter = !empty($litterId) && is_int($litterId) ? " AND litter_id = $litterId " : '';

        $ramType = AnimalObjectType::Ram;
        $eweType = AnimalObjectType::Ewe;

        $minimumPercentageOfOffspringWithEnoughMuscularity = 75;
        $minimumMuscularityOfOffspring = 80;

        $predicateTypePreferentJoinedList = SqlUtil::predicateTypePreferentJoinedList();
        $definitiveExteriorKindsJoinedList = SqlUtil::definitiveExteriorKindsJoinedList();

        $sql = "UPDATE
    litter
SET
    ewes_with_definitive_exterior_count = v.ewes_with_definitive_exterior_count,
    rams_with_definitive_exterior_count = v.rams_with_definitive_exterior_count,
    vg_rams_if_father_no_def_exterior_count = v.vg_rams_if_father_no_def_exterior_count,
    definitive_prime_ram_count = v.definitive_prime_ram_count,
    grade_ram_count = v.grade_ram_count,
    preferent_ram_count = v.preferent_ram_count,
    has_minimum_offspring_muscularity = v.has_minimum_offspring_muscularity
FROM (
         SELECT
             l.id,
             COALESCE(definitive_ewes_count, 0) as definitive_ewes_count,
             COALESCE(definitive_rams_count, 0) as definitive_rams_count,
             COALESCE(vg_exterior_rams_if_father_no_definitive_exterior_count, 0) as vg_exterior_rams_if_father_no_definitive_exterior_count,
             COALESCE(definitive_prime_rams.definitive_prime_ram_count, 0) as definitive_prime_ram_count,
             COALESCE(grade_rams.grade_ram_count, 0) as grade_ram_count,
             COALESCE(preferent_rams.preferent_ram_count, 0) as preferent_ram_count,
             COALESCE(minimum_muscularity.has_80_minimum_muscularity, false) as has_80_minimum_muscularity
         FROM litter l
                  LEFT JOIN (
             SELECT
                 litter_id,
                 COUNT(*) as definitive_ewes_count
             FROM animal a
                      INNER JOIN (
                 SELECT
                     e.animal_id
                 FROM exterior e
                          INNER JOIN measurement m on e.id = m.id
                 WHERE m.is_active AND
                         kind IN ($definitiveExteriorKindsJoinedList)
                 GROUP BY e.animal_id
             )definitive_exterior ON definitive_exterior.animal_id = a.id
             WHERE litter_id NOTNULL AND a.type = '$eweType' $subLitterIdFilter
             GROUP BY litter_id
         )definitive_ewes ON definitive_ewes.litter_id = l.id
                  LEFT JOIN (
             SELECT
                 litter_id,
                 COUNT(*) as definitive_rams_count
             FROM animal a
                      INNER JOIN (
                 SELECT
                     e.animal_id
                 FROM exterior e
                          INNER JOIN measurement m on e.id = m.id
                 WHERE m.is_active AND
                         kind IN ($definitiveExteriorKindsJoinedList)
                 GROUP BY e.animal_id
             )definitive_exterior ON definitive_exterior.animal_id = a.id
             WHERE litter_id NOTNULL AND a.type = '$ramType' $subLitterIdFilter
             GROUP BY litter_id
         )definitive_rams ON definitive_rams.litter_id = l.id
                  LEFT JOIN (
             SELECT
                 litter_id,
                 COUNT(*) as vg_exterior_rams_if_father_no_definitive_exterior_count
             FROM animal a
                      INNER JOIN (
                 SELECT
                     e_offspring.animal_id
                 FROM exterior e_offspring
                          INNER JOIN measurement m_offspring on e_offspring.id = m_offspring.id
                 WHERE m_offspring.is_active AND
                         e_offspring.kind = '".ExteriorKind::VG_."'

                 GROUP BY e_offspring.animal_id
             )vg_exterior ON vg_exterior.animal_id = a.id
             WHERE litter_id NOTNULL AND a.type = '$ramType' AND
                     litter_id IN (
                     -- litters with a father that has a definitive exterior
                     SELECT
                         litter.id
                     FROM exterior e
                              INNER JOIN measurement m on e.id = m.id
                              INNER JOIN animal dad on e.animal_id = dad.id
                              INNER JOIN litter ON litter.animal_father_id = dad.id
                     WHERE m.is_active AND
                             kind IN ($definitiveExteriorKindsJoinedList)
                     GROUP BY litter.id
                 ) $subLitterIdFilter
             GROUP BY litter_id
         )vg_exterior_rams_if_father_no_definitive_exterior ON vg_exterior_rams_if_father_no_definitive_exterior.litter_id = l.id
                  LEFT JOIN (
             SELECT
                 litter_id,
                 COUNT(*) as definitive_prime_ram_count
             FROM animal
             WHERE breed_type = '".PredicateType::DEFINITIVE_PREMIUM_RAM."' $subLitterIdFilter
             GROUP BY litter_id
         )definitive_prime_rams ON definitive_prime_rams.litter_id = l.id
                  LEFT JOIN (
             SELECT
                 litter_id,
                 COUNT(*) as grade_ram_count
             FROM animal
             WHERE breed_type = '".PredicateType::GRADE_RAM."' $subLitterIdFilter
             GROUP BY litter_id
         )grade_rams ON grade_rams.litter_id = l.id
                  LEFT JOIN (
             SELECT
                 litter_id,
                 COUNT(*) as preferent_ram_count
             FROM animal
             WHERE breed_type IN ($predicateTypePreferentJoinedList) $subLitterIdFilter
             GROUP BY litter_id
         )preferent_rams ON preferent_rams.litter_id = l.id
                  LEFT JOIN (
             SELECT
                 definitive_offspring.litter_id,
                 definitive_offspring_count,
                 COALESCE(has_80_minimum_muscularity_count,0) as has_80_minimum_muscularity_count,
                 ((COALESCE(has_80_minimum_muscularity_count,0) * 100) / definitive_offspring_count) > $minimumPercentageOfOffspringWithEnoughMuscularity
                                                              as has_80_minimum_muscularity
             FROM (
                      SELECT
                          litter_id,
                          COUNT(*) as definitive_offspring_count
                      FROM animal a
                               INNER JOIN (
                          SELECT
                              e.animal_id
                          FROM exterior e
                                   INNER JOIN measurement m on e.id = m.id
                          WHERE m.is_active AND
                                  kind IN ($definitiveExteriorKindsJoinedList)
                          GROUP BY e.animal_id
                      )definitive_exterior ON definitive_exterior.animal_id = a.id
                      WHERE litter_id NOTNULL $subLitterIdFilter
                      GROUP BY litter_id
                  )definitive_offspring
                      LEFT JOIN (
                 SELECT
                     litter_id,
                     COUNT(*) as has_80_minimum_muscularity_count
                 FROM animal a
                          INNER JOIN (
                     SELECT
                         e.animal_id
                     FROM exterior e
                              INNER JOIN measurement m on e.id = m.id
                     WHERE m.is_active AND
                             kind IN ($definitiveExteriorKindsJoinedList)
                       AND muscularity >= $minimumMuscularityOfOffspring
                     GROUP BY e.animal_id
                 )definitive_exterior ON definitive_exterior.animal_id = a.id
                 WHERE litter_id NOTNULL $subLitterIdFilter
                 GROUP BY litter_id
             )minimum_muscularity ON minimum_muscularity.litter_id = definitive_offspring.litter_id
         )minimum_muscularity ON minimum_muscularity.litter_id = l.id
         WHERE (
                           l.ewes_with_definitive_exterior_count <> COALESCE(definitive_ewes_count, 0) OR
                           l.rams_with_definitive_exterior_count <> COALESCE(definitive_rams_count, 0) OR
                           l.vg_rams_if_father_no_def_exterior_count <> COALESCE(vg_exterior_rams_if_father_no_definitive_exterior_count, 0) OR
                           l.definitive_prime_ram_count <> COALESCE(definitive_prime_rams.definitive_prime_ram_count, 0) OR
                           l.grade_ram_count <> COALESCE(grade_rams.grade_ram_count, 0) OR
                           l.preferent_ram_count <> COALESCE(preferent_rams.preferent_ram_count, 0) OR
                           l.has_minimum_offspring_muscularity <> COALESCE(minimum_muscularity.has_80_minimum_muscularity, false)
                   )
) AS v(
            litter_id,
            ewes_with_definitive_exterior_count,
            rams_with_definitive_exterior_count,
            vg_rams_if_father_no_def_exterior_count,
            definitive_prime_ram_count,
            grade_ram_count,
            preferent_ram_count,
            has_minimum_offspring_muscularity
    ) WHERE litter.id = v.litter_id $litterIdFilter";

        $updateCount = SqlUtil::updateWithCount($conn, $sql);

        if ($logger) {
            $logger->notice("$updateCount litters updated with new offspring exterior count values");
        }

        return $updateCount;
    }


    private static function updateLitterStarEweBasePoints(Connection $conn, $litterId = null, ?Logger $logger = null): int
    {
        if ($logger) {
            $updateType = !empty($litterId) && is_int($litterId) ? "litter with id $litterId" : "ALL litters";
            $logger->notice("Update $updateType starEweBasePoints ...");
        }

        $litterIdFilter = !empty($litterId) && is_int($litterId) ? " AND l.id = $litterId " : '';
        $subLitterIdFilter = !empty($litterId) && is_int($litterId) ? " AND litter_id = $litterId " : '';

        $definitiveExteriorKindsJoinedList = SqlUtil::definitiveExteriorKindsJoinedList();

        $ramType = AnimalObjectType::Ram;
        $eweType = AnimalObjectType::Ewe;

        $sql = "UPDATE litter SET star_ewe_base_points = v.star_ewe_base_points
FROM (   
         SELECT
             l.id as litter_id,
             COALESCE(definitive_graded_daughters.star_ewe_points, 0) +
             COALESCE(definitive_graded_sons.star_ewe_points, 0) +
             COALESCE(preliminary_graded_sons.star_ewe_points, 0) as star_ewe_base_points
         FROM litter l
                  LEFT JOIN (
             SELECT
                 litter_id,
                 SUM(star_ewe_points) as star_ewe_points
             FROM (
                      SELECT
                          animal.litter_id,
                          CASE
                              WHEN 75 <= e.general_appearance AND e.general_appearance <= 79 THEN
                                  1
                              WHEN 80 <= e.general_appearance AND e.general_appearance <= 84 THEN
                                  3
                              WHEN 85 <= e.general_appearance AND e.general_appearance <= 89 THEN
                                  5
                              WHEN 90 <= e.general_appearance THEN
                                  6
                              ELSE 0 END
                              as star_ewe_points
                      FROM animal
                               INNER JOIN exterior e on animal.id = e.animal_id
                               INNER JOIN measurement m on e.id = m.id
                      WHERE m.is_active AND e.general_appearance NOTNULL AND 75 <= e.general_appearance
                        AND litter_id NOTNULL $subLitterIdFilter AND animal.type = '$eweType'
                        AND kind IN ($definitiveExteriorKindsJoinedList)
                  )ewe_star_ewe_points
             GROUP BY litter_id
         )definitive_graded_daughters ON definitive_graded_daughters.litter_id = l.id
                  LEFT JOIN (
             SELECT
                 litter_id,
                 SUM(star_ewe_points) as star_ewe_points
             FROM (
                      SELECT
                          animal.litter_id,
                          CASE
                              WHEN 75 <= e.general_appearance AND e.general_appearance <= 79 THEN
                                  2
                              WHEN 80 <= e.general_appearance AND e.general_appearance <= 84 THEN
                                  4
                              WHEN 85 <= e.general_appearance AND e.general_appearance <= 89 THEN
                                  6
                              WHEN 90 <= e.general_appearance THEN
                                  7
                              ELSE 0 END
                              as star_ewe_points
                      FROM animal
                               INNER JOIN exterior e on animal.id = e.animal_id
                               INNER JOIN measurement m on e.id = m.id
                      WHERE m.is_active AND e.general_appearance NOTNULL AND litter_id NOTNULL $subLitterIdFilter AND animal.type = '$ramType'
                        AND kind IN ($definitiveExteriorKindsJoinedList)
                  )ram_star_ewe_points
             GROUP BY litter_id
         )definitive_graded_sons ON definitive_graded_sons.litter_id = l.id
                  LEFT JOIN (
             SELECT
                 litter_id,
                 SUM(star_ewe_points) as star_ewe_points
             FROM (
                      SELECT
                          animal.litter_id,
                          CASE
                              WHEN 75 <= e.general_appearance AND e.general_appearance <= 79 THEN
                                  0
                              WHEN 80 <= e.general_appearance AND e.general_appearance <= 84 THEN
                                  1
                              WHEN 85 <= e.general_appearance AND e.general_appearance <= 89 THEN
                                  2
                              WHEN 90 <= e.general_appearance THEN
                                  2
                              ELSE 0 END
                              as star_ewe_points
                      FROM animal
                        INNER JOIN (
                          SELECT
                              animal_id,
                              general_appearance
                          FROM (
                                   SELECT
                                       animal_id,
                                       MAX(e.general_appearance) as general_appearance,
                                       SUM(CASE WHEN kind = '".ExteriorKind::VG_."' THEN 1 ELSE 0 END) as vg_count
                                   FROM animal
                                            INNER JOIN exterior e on animal.id = e.animal_id
                                            INNER JOIN measurement m on e.id = m.id
                                   WHERE m.is_active AND e.general_appearance NOTNULL AND litter_id NOTNULL AND animal.type = '$ramType'
                                   GROUP BY e.animal_id
                                   -- only include animals that are NOT definitively graded yet
                                   HAVING SUM(CASE WHEN (kind IN ($definitiveExteriorKindsJoinedList)) THEN 1 ELSE 0 END) = 0
                               )exteriors
                          WHERE exteriors.vg_count > 0
                          )e ON e.animal_id = animal.id
                        WHERE litter_id NOTNULL AND animal.type = '$ramType'
                  )ram_star_ewe_points
             GROUP BY litter_id
         )preliminary_graded_sons ON preliminary_graded_sons.litter_id = l.id
         WHERE l.star_ewe_base_points <> (
                 COALESCE(definitive_graded_daughters.star_ewe_points, 0) +
                 COALESCE(definitive_graded_sons.star_ewe_points, 0) +
                 COALESCE(preliminary_graded_sons.star_ewe_points, 0)
             ) $litterIdFilter
     ) AS v(litter_id, star_ewe_base_points) WHERE litter.id = v.litter_id";

        $updateCount = SqlUtil::updateWithCount($conn, $sql);

        if ($logger) {
            $logger->notice("$updateCount litters updated with new starEweBasePoints");
        }

        return $updateCount;
    }
}
