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


}