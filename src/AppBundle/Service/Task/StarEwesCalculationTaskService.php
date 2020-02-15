<?php


namespace AppBundle\Service\Task;

use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Enumerator\BreedType;
use AppBundle\Util\LitterUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\SqlUtil;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;

class StarEwesCalculationTaskService
{
    const TITLE = 'star_ewes_calculation';

    /** @var EntityManager  */
    private $em;

    /** @var Logger  */
    private $logger;

    /**
     * StarEwesCalculationTaskService constructor.
     * @param EntityManager $em
     * @param Logger $logger
     */
    public function __construct(
        EntityManager $em,
        Logger $logger
    )
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @param Person $person
     * @param Location|null $location
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    function calculate(Person $person, ?Location $location = null)
    {
        // LOGIC HERE!!
        try {
            $this->prepareLitterData();

            return ResultUtil::successResult('ok');
        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }

    private function prepareLitterData()
    {
        $this->logger->notice('Updating litter ordinals...');
        $updatedLitterOrdinalCount = LitterUtil::updateLitterOrdinals($this->em->getConnection());
        $this->logger->notice($updatedLitterOrdinalCount.' litter ordinals updated');
        $removeLitterOrdinalCount = LitterUtil::removeLitterOrdinalFromRevokedLitters($this->em->getConnection());
        $this->logger->notice($removeLitterOrdinalCount.' litter ordinals removed from revoked litters');

        $this->logger->notice('Updating litter cumulativeBornAliveCount values ...');
        $cumulativeBornAliveCountUpdates = LitterUtil::updateCumulativeBornAliveCount($this->em->getConnection());
        $this->logger->notice($cumulativeBornAliveCountUpdates.' cumulativeBornAliveCount values updated');

        LitterUtil::updateLitterOffspringExteriorAndStarEweValues($this->em->getConnection(), null, $this->logger);
    }


    /**
     * Notes on query:
     *
     * standard_litter_ordinal NOTNULL -- will check is litter has "active" request status and is not an abortion nor pseudo pregnancy
     *
     * @return string
     */
    public static function queryStarEweQualifiedEwesWithPoints(): string
    {
        return "
SELECT
    star_ewe.id,
    star_ewe_base_points,
    star_ewe_points_including_bonus_points_of_sons
FROM animal star_ewe

    INNER JOIN (
        ".self::queryStarEweCriteria1()."
    )criteria_1 ON criteria_1.animal_id = star_ewe.id

    INNER JOIN (
        ".self::queryStarEweCriteria2()."
    )criteria_2 ON criteria_2.animal_id = star_ewe.id

    INNER JOIN (
        ".self::queryStarEweCriteria3a()."
    )criteria_3a ON criteria_3a.animal_mother_id = star_ewe.id

    INNER JOIN (
        ".self::queryStarEweCriteria3b()."
    )criteria_3b ON criteria_3b.animal_mother_id = star_ewe.id

    INNER JOIN (
        ".self::queryStarEweCriteria3c()."
    )criteria_3c ON criteria_3c.animal_mother_id = star_ewe.id

    INNER JOIN (
        ".self::queryStarEweCriteria4a()."
    )criteria_4a ON criteria_4a.animal_mother_id = star_ewe.id

    LEFT JOIN (
        ".self::queryStarEweCriteria4b()."
    )criteria_4b ON criteria_4b.mother_id_offspring_having_birth_defects = star_ewe.id

    INNER JOIN (
        ".self::queryStarEweCriteria4cPart1()."
    )criteria_4c_part1 ON criteria_4c_part1.animal_mother_id = star_ewe.id

    INNER JOIN (
        ".self::queryStarEweCriteria4cPart2()."
    )criteria_4c_part2 ON criteria_4c_part2.animal_mother_id = star_ewe.id

    WHERE
        criteria_4b.has_offspring_with_birth_defects ISNULL
";
    }

    /**
     * Star ewe criteria 1
     *
     * De ooi moet bij minstens 1 van al haar DF, DD, HK, of HH metingen.
     * Minimaal 80 punten voor algemeen voorkomen hebben behaald.
     *
     * @return string
     */
    private static function queryStarEweCriteria1(): string
    {
        $definitiveExteriorKindsJoinedList = SqlUtil::definitiveExteriorKindsJoinedList();
        return "SELECT
            animal_id
        FROM
            (
                   SELECT
                       x.animal_id
                   FROM exterior x
                            INNER JOIN measurement m on x.id = m.id
                            INNER JOIN animal a on x.animal_id = a.id
                   WHERE m.is_active AND
                           x.kind IN ($definitiveExteriorKindsJoinedList)
                     AND x.general_appearance NOTNULL AND x.general_appearance >= 80
                     AND a.type = '".AnimalObjectType::Ewe."'
        )criteria_1_ungrouped
        GROUP BY animal_id";
    }

    /**
     * Star ewe criteria 2
     *
     * De ooi dient bij de laatste beoordeling minimaal 80 punten voor bespiering te hebben behaald.
     *
     * @return string
     */
    private static function queryStarEweCriteria2(): string
    {
        return "SELECT
            animal_id
        FROM (
                 SELECT
                     x.animal_id
                 FROM exterior x
                          INNER JOIN animal a on x.animal_id = a.id
                          INNER JOIN (
                     -- exteriors with max id of those of max measurement date
                     SELECT
                         x.animal_id,
                         max(x.id) as max_id
                     FROM exterior x
                              INNER JOIN measurement m on x.id = m.id
                              INNER JOIN (
                         -- exteriors with max measurement date
                         SELECT
                             animal_id,
                             max(m.measurement_date) as measurement_date
                         FROM exterior x
                                  INNER JOIN measurement m on x.id = m.id
                         WHERE m.is_active GROUP BY animal_id
                     )max_date_x ON max_date_x.animal_id = x.animal_id AND max_date_x.measurement_date = m.measurement_date
                     WHERE m.is_active GROUP BY x.animal_id
                 )last_x ON last_x.max_id = x.id
                 WHERE x.muscularity NOTNULL AND x.muscularity >= 80 AND a.type = '".AnimalObjectType::Ewe."'
             )criteria_2_ungrouped
        GROUP BY animal_id -- The group by should actually not make a difference here. Added just in case.";
    }

    /**
     * Star ewe criteria 3a
     *
     * Ooi is minstens 4 jaar oud, based on litter dates.
     *
     * @return string
     */
    private static function queryStarEweCriteria3a(): string
    {
        return "SELECT
            l.animal_mother_id
        FROM litter l
        WHERE
          -- ewe should be at least 4 years old
            (
                    l.litter_date NOTNULL AND
                    EXTRACT(YEAR FROM AGE(NOW(), l.litter_date)) >= 4
                )
          AND standard_litter_ordinal NOTNULL
        GROUP BY l.animal_mother_id";
    }

    /**
     * Star ewe criteria 3b
     *
     * Ooi heeft minstens 3x gelamd.
     *
     * @return string
     */
    private static function queryStarEweCriteria3b(): string
    {
        return "SELECT
            l.animal_mother_id
        FROM litter l
        WHERE standard_litter_ordinal NOTNULL
        GROUP BY l.animal_mother_id HAVING MAX(standard_litter_ordinal) >= 3";
    }

    /**
     * Star ewe criteria 3c
     *
     * @return string
     */
    private static function queryStarEweCriteria3c(): string
    {
        return "SELECT
            animal_mother_id
        FROM (
                 SELECT
                     animal_mother_id,
                     standard_litter_ordinal,
                     cumulative_born_alive_count,
                     (
                        l.standard_litter_ordinal < 3 OR -- Necessary check to prevent invalidating litters to be ignored
                        l.standard_litter_ordinal = 3 AND 5 <= cumulative_born_alive_count OR
                        l.standard_litter_ordinal = 4 AND 7 <= cumulative_born_alive_count OR
                        l.standard_litter_ordinal = 5 AND 9 <= cumulative_born_alive_count OR
                        l.standard_litter_ordinal = 6 AND 11 <= cumulative_born_alive_count OR
                        6 < l.standard_litter_ordinal -- Necessary check to prevent invalidating litters to be ignored
                     ) as has_valid_offspring_count_for_fertility
                 FROM litter l
                          INNER JOIN animal a on l.animal_mother_id = a.id
                 WHERE a.date_of_birth NOTNULL
                   AND standard_litter_ordinal NOTNULL
                   AND ( --age_in_months_at_litter_date
                                   EXTRACT(YEAR FROM AGE(l.litter_date, a.date_of_birth)) * 12 + --get months from year
                                   EXTRACT(MONTH FROM AGE(l.litter_date, a.date_of_birth))
                           ) > 18 -- litters at age OLDER THAN 1 year (18 months)
                   AND ( --age_in_months_at_litter_date
                                   EXTRACT(YEAR FROM AGE(l.litter_date, a.date_of_birth)) * 12 + --get months from year
                                   EXTRACT(MONTH FROM AGE(l.litter_date, a.date_of_birth))
                           ) <= (6 * 12 + 6) -- Litters are AT MOST 6 years and 6 months old
                 ORDER BY animal_mother_id, standard_litter_ordinal
             )fertility_cumulative_offspring_validation
        GROUP BY animal_mother_id HAVING bool_and(has_valid_offspring_count_for_fertility)";
    }

    /**
     * Star ewe criteria 4a
     *
     * @return string
     */
    private static function queryStarEweCriteria4a(): string
    {
        return "SELECT
    animal_mother_id
FROM (
         SELECT
             animal_mother_id,
             EXTRACT(YEAR FROM AGE(l.litter_date, a.date_of_birth)) <= cumulative_born_alive_count as has_valid_offspring_count
         FROM litter l
                  INNER JOIN animal a on l.animal_mother_id = a.id
         WHERE
             standard_litter_ordinal NOTNULL
           AND
           -- Month of the age should be between 0 and 6
                 EXTRACT(MONTH FROM AGE(l.litter_date, a.date_of_birth)) <= 6
           AND
           -- Minimum age is 4 years
                 EXTRACT(YEAR FROM AGE(l.litter_date, a.date_of_birth)) >= 4
     )count_results
GROUP BY animal_mother_id HAVING bool_and(has_valid_offspring_count)";
    }

    /**
     * Star ewe criteria 4b
     *
     * De nakomelingen mogen geen erfelijke gebreken hebben.
     * Deze informatie zit deels verstopt in de general appearance.
     *
     * @return string
     */
    private static function queryStarEweCriteria4b(): string
    {
        return "SELECT
            parent_mother_id as mother_id_offspring_having_birth_defects,
            true as has_offspring_with_birth_defects
        FROM animal
                 INNER JOIN exterior e on animal.id = e.animal_id
        WHERE parent_mother_id NOTNULL AND animal.type = '".AnimalObjectType::Ewe."' AND
            (
                general_appearance <= 69 AND
                breed_type = '".BreedType::BLIND_FACTOR."'
            )
        GROUP BY parent_mother_id";
    }

    /**
     * Star ewe criteria 4c part 1
     *
     * @return string
     */
    private static function queryStarEweCriteria4cPart1(): string
    {
        return "SELECT
            animal_mother_id
        FROM (
                 SELECT
                     l.standard_litter_ordinal,
                     l.animal_mother_id,
                     cumulative_offspring_definitive_exterior_count,
                     year_of_age_of_mother_at_litter_date,
                     (year_of_age_of_mother_at_litter_date - 2) <= cumulative_offspring_definitive_exterior_count
                         as has_valid_offspring_with_definitive_exterior_count
                 FROM litter l
                          INNER JOIN animal a on l.animal_mother_id = a.id
                          INNER JOIN (
                     SELECT
                         l.id as litter_id,
                         l.standard_litter_ordinal,
                         l.animal_mother_id,
                         SUM(l.ewes_with_definitive_exterior_count + l.rams_with_definitive_exterior_count)
                         OVER (PARTITION BY animal_mother_id ORDER BY standard_litter_ordinal) as cumulative_offspring_definitive_exterior_count,
                         EXTRACT(YEAR FROM AGE(l.litter_date, a.date_of_birth)) as year_of_age_of_mother_at_litter_date,
                         EXTRACT(MONTH FROM AGE(l.litter_date, a.date_of_birth)) as month_of_age_of_mother_at_litter_date
                     FROM litter l
                              INNER JOIN animal a on l.animal_mother_id = a.id
                     WHERE standard_litter_ordinal NOTNULL
                 )definitive_offspring ON definitive_offspring.litter_id = l.id
                 WHERE
                     l.standard_litter_ordinal NOTNULL AND
                   -- Month of the age should be between 0 and 6
                         month_of_age_of_mother_at_litter_date <= 6
                   AND
                   -- Minimum age is 4 years
                         year_of_age_of_mother_at_litter_date >= 4
             )cumulative_definitive_offspring_per_litter
        GROUP BY animal_mother_id HAVING bool_and(has_valid_offspring_with_definitive_exterior_count)";
    }

    /**
     * Star ewe criteria 4c part 2
     *
     * @return string
     */
    private static function queryStarEweCriteria4cPart2(): string
    {
        return "SELECT
            animal_mother_id,
            SUM(star_ewe_base_points) as star_ewe_base_points,
            SUM(
                definitive_prime_ram_count * 2 +
                grade_ram_count * 4 +
                preferent_ram_count * 10 +
                star_ewe_base_points
            ) as star_ewe_points_including_bonus_points_of_sons
        FROM litter
        WHERE animal_mother_id NOTNULL
          AND standard_litter_ordinal NOTNULL
        GROUP BY animal_mother_id HAVING SUM(star_ewe_base_points) >= 13";
    }
}
