<?php


namespace AppBundle\Service\DataFix;


use AppBundle\Entity\Employee;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\EditTypeEnum;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\SqlUtil;

class UbnHistoryFixer extends DuplicateFixerBase
{

    public function fixAllAnimalResidenceRecordsByCurrentAnimalLocation(): int
    {
        $this->removeDuplicateAnimalResidences();
        $updateCount = $this->closeInvalidOpenResidencesByTodayDateForRemovedAnimals();
        return $this->closeInvalidOpenResidencesByTodayDateForRelocatedAnimals() + $updateCount;
    }


    public function fixAnimalResidenceRecordsByCurrentAnimalLocationWithQuestion(CommandUtil $cmdUtil): int
    {
        do {
            $locationId = $cmdUtil->questionForIntChoice(262,'location primary key');
            /** @var Location $location */
            $location = $this->em->getRepository(Location::class)->find($locationId);

        } while (empty($location));

        $this->logger->notice('UBN: '.$location->getUbn());

        $updateCount = $this->fixAnimalResidenceRecordsByCurrentAnimalLocationOfLocationId($locationId);
        $this->logger->notice('Update count '.$updateCount);
        return $updateCount;
    }


    /**
     * @param  int  $locationId
     * @return int
     */
    public function fixAnimalResidenceRecordsByCurrentAnimalLocationOfLocationId(int $locationId): int
    {
        $this->removeDuplicateAnimalResidences();
        $updateCount = $this->closeInvalidOpenResidencesByTodayDateForRemovedAnimals($locationId);
        return $this->closeInvalidOpenResidencesByTodayDateForRelocatedAnimals($locationId) + $updateCount;
    }


    /**
     * Only run this query if all relocations (arrivals, departs, imports, exports) are processed!
     */
    public function forceFixAnimalResidenceRecords() {
        $this->removeDuplicateAnimalResidences();
        // Always remove ALL duplicates first before closing the residences!
        $this->closeOpenResidencesWithMatchedYoungerResidence();
        // Always close residences with younger residences BEFORE closing them by dateOfBirth!
        $this->closeOpenResidencesWithDateOfDeath();
    }

    /**
     * A race condition between a retrieve animal sync and an declare arrival or declare import
     * could result in duplicate animal residence records
     */
    private function removeDuplicateAnimalResidences()
    {
        $this->logger->info('Delete duplicate animal_residences');
        $this->logger->info('ignoring hours in startDate and endDate');

        $sqlQueries = [
            "Delete duplicate animal residences where at least one is closed and the others are not" =>
                self::sqlQueryDeleteDuplicateAnimalResidencesWhereAtLeastOneIsClosedAndTheOthersAreNot(),
            "Delete duplicate animal residences by animal, location, dates, country and is_pending" =>
                self::sqlQueryDeleteDuplicateAnimalResidencesByAnimalLocationDatesCountryAndIsPending(),
            "Delete pending animal residences which have a duplicate non pending version" =>
                self::sqlQueryDeletePendingAnimalResidencesWhichHaveADuplicateNonPendingVersion(),
            "Delete identical animal residences for which a new version already exists in the new location record" =>
                self::sqlQueryDeleteIdenticalAnimalResidencesForWhichANewVersionAlreadyExistsInTheNewLocationRecord(),
        ];

        $totalDeleteCount = 0;

        foreach ($sqlQueries as $title => $sql) {
            $this->logger->info($title);
            $deleteCount = SqlUtil::updateWithCount($this->conn, $sql);
            $totalDeleteCount += $deleteCount;
            $this->logger->info('Deleted '.$deleteCount.'|'.$totalDeleteCount.' [sub|total]');
        }

        $countPrefix = $totalDeleteCount === 0 ? 'No' : $totalDeleteCount ;
        $this->logger->info($countPrefix.' duplicate animal_residences deleted in total');

        if($totalDeleteCount > 0) { DoctrineUtil::updateTableSequence($this->conn, ['animal_residence']); }
    }


    private static function sqlQueryDeleteDuplicateAnimalResidencesWhereAtLeastOneIsClosedAndTheOthersAreNot(): string
    {
        return "DELETE FROM animal_residence WHERE id IN (
                SELECT
                    r.id as duplicate_unclosed_residence_id
                FROM animal_residence r
                         INNER JOIN (
                    SELECT
                        r.animal_id,
                        DATE(r.start_date) as start_date,
                        r.location_id,
                        bool_or(r.end_date NOTNULL AND is_pending = FALSE) as has_residence_with_end_date
                    FROM animal_residence r
                    GROUP BY r.animal_id, r.location_id, DATE(r.start_date) HAVING COUNT(*) > 1
                )duplicate ON 
                    duplicate.animal_id = r.animal_id AND 
                    duplicate.location_id = r.location_id AND 
                    DATE(duplicate.start_date) = DATE(r.start_date)
                WHERE duplicate.has_residence_with_end_date AND r.end_date ISNULL
                ORDER BY r.animal_id, r.start_date
                )";
    }


    private static function sqlQueryDeleteDuplicateAnimalResidencesByAnimalLocationDatesCountryAndIsPending(): string
    {
        return "DELETE FROM animal_residence WHERE id IN (
                  -- WHERE end date is null
                  SELECT
                    rr.id as duplicate_residence_id
                  FROM animal_residence rr
                    INNER JOIN (
                                 SELECT
                                   r.animal_id, r.location_id,
                                   DATE(r.start_date) as start_date,
                                   --DATE(r.end_date) as end_date,
                                   r.country, r.is_pending,
                                   MIN(id) as min_id
                                 FROM animal_residence r
                                 WHERE r.end_date ISNULL
                                 GROUP BY r.animal_id, r.location_id, DATE(r.start_date),
                                   --DATE(r.end_date),
                                   r.country, r.is_pending HAVING COUNT(*) > 1
                               )r ON r.animal_id = rr.animal_id AND
                                     r.location_id = rr.location_id AND
                                     r.start_date = DATE(rr.start_date) AND
                                     --r.end_date = DATE(rr.end_date) AND
                                     r.country = rr.country AND
                                     r.is_pending = rr.is_pending AND
                                     rr.id <> r.min_id
                  WHERE rr.end_date ISNULL
                
                  UNION
                
                  -- WHERE end date is not null
                  SELECT
                    rr.id as duplicate_residence_id
                  FROM animal_residence rr
                    INNER JOIN (
                                 SELECT
                                   r.animal_id, r.location_id,
                                   DATE(r.start_date) as start_date,
                                   DATE(r.end_date) as end_date,
                                   r.country, r.is_pending,
                                   MIN(id) as min_id
                                 FROM animal_residence r
                                 GROUP BY r.animal_id, r.location_id, DATE(r.start_date),
                                   DATE(r.end_date),
                                   r.country, r.is_pending HAVING COUNT(*) > 1
                               )r ON r.animal_id = rr.animal_id AND
                                     r.location_id = rr.location_id AND
                                     r.start_date = DATE(rr.start_date) AND
                                     r.end_date = DATE(rr.end_date) AND
                                     r.country = rr.country AND
                                     r.is_pending = rr.is_pending AND
                                     rr.id <> r.min_id
                )";
    }


    private static function sqlQueryDeletePendingAnimalResidencesWhichHaveADuplicateNonPendingVersion(): string
    {
        return "DELETE FROM animal_residence WHERE id IN (
              SELECT
                r_pending.id as duplicate_pending_residence_id
              FROM animal_residence r_not_pending
                INNER JOIN (
                             SELECT
                               r.animal_id, r.location_id,
                               DATE(r.start_date) as start_date,
                               r.country,
                               MIN(id) as min_id
                             FROM animal_residence r
                             WHERE r.end_date ISNULL
                             GROUP BY r.animal_id, r.location_id, DATE(r.start_date),
                               r.country
                             HAVING COUNT(*) = 2
                           )r ON r.animal_id = r_not_pending.animal_id AND
                                 r.location_id = r_not_pending.location_id AND
                                 r.start_date = DATE(r_not_pending.start_date) AND
                                 r.country = r_not_pending.country
                INNER JOIN animal_residence r_pending
                  ON r.animal_id = r_pending.animal_id AND
                     r.location_id = r_pending.location_id AND
                     r.start_date = DATE(r_pending.start_date) AND
                     r.country = r_pending.country
              WHERE r_not_pending.end_date ISNULL AND r_pending.end_date ISNULL
                    AND r_not_pending.is_pending = FALSE AND r_pending.is_pending
            
              UNION
            
              SELECT
                r_pending.id as duplicate_pending_residence_id
              FROM animal_residence r_not_pending
                INNER JOIN (
                             SELECT
                               r.animal_id, r.location_id,
                               DATE(r.start_date) as start_date,
                               DATE(r.end_date) as end_date,
                               r.country,
                               MIN(id) as min_id
                             FROM animal_residence r
                             GROUP BY r.animal_id, r.location_id, DATE(r.start_date),
                               DATE(r.end_date),
                               r.country
                             HAVING COUNT(*) = 2
                           )r ON r.animal_id = r_not_pending.animal_id AND
                                 r.location_id = r_not_pending.location_id AND
                                 r.start_date = DATE(r_not_pending.start_date) AND
                                 r.end_date = DATE(r_not_pending.end_date) AND
                                 r.country = r_not_pending.country
                INNER JOIN animal_residence r_pending
                  ON r.animal_id = r_pending.animal_id AND
                     r.location_id = r_pending.location_id AND
                     r.start_date = DATE(r_pending.start_date) AND
                     r.country = r_pending.country
              WHERE r_not_pending.is_pending = FALSE AND r_pending.is_pending
            )
        ";
    }


    private function sqlQueryDeleteIdenticalAnimalResidencesForWhichANewVersionAlreadyExistsInTheNewLocationRecord(): string
    {
        return "DELETE FROM animal_residence WHERE id IN (
                    SELECT
                --     r1.location_id,
                --     r2.location_id,
                --     l1.ubn,
                --     l2.ubn,
                --     l1.is_active,
                --     l2.is_active,
                --     r1.id,
                --     r2.id,
                CASE WHEN l2.is_active THEN
                         r1.id
                     ELSE
                         r2.id
                    END as residence_id_to_deactivate
                    FROM animal_residence r1
                             INNER JOIN animal_residence r2 ON
                                r1.animal_id = r2.animal_id AND
                                DATE(r1.start_date) = DATE(r2.start_date) AND
                                r1.end_date ISNULL AND r2.end_date ISNULL AND
                                r1.is_pending = FALSE AND r2.is_pending = FALSE AND
                                r1.location_id <> r2.location_id AND
                                r1.location_id < r2.location_id
                             INNER JOIN location l1 ON l1.id = r1.location_id
                             INNER JOIN location l2 ON l2.id = r2.location_id
                    WHERE l1.ubn = l2.ubn AND l1.is_active <> l2.is_active
                    )";
    }


    private function closeOpenResidencesWithMatchedYoungerResidence()
    {
        $subQuery = "SELECT DENSE_RANK() OVER (PARTITION BY r.animal_id ORDER BY start_date ASC) AS animal_residence_ordinal,
                    r.*
             FROM animal_residence r
             WHERE is_pending = FALSE
               AND r.animal_id IN (
                 SELECT animal_id
                 FROM animal_residence r
                 WHERE is_pending = FALSE
                 GROUP BY animal_id
                 HAVING COUNT(*) > 1
             )";

        $query = "SELECT
                             lr.id as older_residence_id,
                             lr.animal_id,
                             -- lr.location_id as older_location_id,
                             -- rr.location_id as younger_location_id,
                             -- lr.start_date as older_residence_start_date,
                             rr.start_date as younger_residence_start_date
                         FROM (
                                  $subQuery
                              ) lr -- left record, with earlier start_date
                                  INNER JOIN (
                             $subQuery
                         ) rr -- right record, with later start_date
                                             ON lr.animal_id = rr.animal_id AND
                                                lr.animal_residence_ordinal + 1 = rr.animal_residence_ordinal
                         WHERE lr.end_date ISNULL";

        $this->batchCloseOpenResidencesBase(
            EditTypeEnum::CLOSE_END_DATE_BY_NEXT_RECORD,
            $query,
            'Residences closed by matched younger residences'
        );
    }

    private function batchCloseOpenResidencesBase(
        int $editTypeEnum, string $selectQuery,
        string $logMessage
    )
    {
        $sql = "UPDATE
                    animal_residence
                SET
                    end_date_edit_type = $editTypeEnum,
                    end_date = v.new_end_date
                FROM (
                        $selectQuery
                    ) as v(residence_id, animal_id, new_end_date)
                WHERE
                      v.residence_id = animal_residence.id AND
                      v.animal_id = animal_residence.animal_id AND
                      animal_residence.end_date ISNULL
                ";

        $updateCount = SqlUtil::updateWithCount($this->conn, $sql);
        $this->logger->info($logMessage.': '.$updateCount);
    }

    private function closeOpenResidencesWithDateOfDeath()
    {
        $query = "SELECT
       r.id as residence_id,
       r.animal_id,
       DATE(a.date_of_death) date_of_death
FROM animal_residence r
INNER JOIN animal a ON r.animal_id = a.id
INNER JOIN (
    SELECT
        marked_residences.animal_id,
        SUM (CASE WHEN is_last_residence THEN id ELSE 0 END) id_last_residence,
        bool_and(end_date_state_matches_update_query_requirement) as end_date_state_matches_update_query_requirement
    FROM (
             SELECT
                 r.animal_id,
                 r.id,
                -- CHECK IF ALL RESIDENCES ARE CLOSED EXCEPT THE LAST ONE
                 (CASE WHEN (
                     SELECT DENSE_RANK() OVER (PARTITION BY r.animal_id ORDER BY start_date ASC) -- ordinal
                                = last_residence.max_ordinal -- is last residence
                 ) THEN
                           r.end_date ISNULL -- last residence should be open
                       ELSE
                           r.end_date NOTNULL -- non-last residences should be closed
                     END) as end_date_state_matches_update_query_requirement,
                 (SELECT DENSE_RANK() OVER (PARTITION BY r.animal_id ORDER BY start_date ASC) = last_residence.max_ordinal) as is_last_residence
             FROM animal_residence r
                      INNER JOIN (
                 SELECT
                     animal_id,
                     max(animal_residence_ordinal) as max_ordinal
                 FROM (
                          SELECT DENSE_RANK() OVER (PARTITION BY r.animal_id ORDER BY start_date ASC) AS animal_residence_ordinal,
                                 r.*
                          FROM animal_residence r
                            INNER JOIN animal a on r.animal_id = a.id
                          WHERE is_pending = FALSE
                            AND a.date_of_death NOTNULL
                            AND r.animal_id IN (
                              SELECT animal_id
                              FROM animal_residence r
                              WHERE is_pending = FALSE
                              GROUP BY animal_id
                          )
                      )ordered_residences
                 GROUP BY animal_id
             )last_residence ON last_residence.animal_id = r.animal_id
            WHERE is_pending = FALSE
         )marked_residences
    GROUP BY animal_id HAVING bool_and(end_date_state_matches_update_query_requirement)
    )last_marked_residences ON last_marked_residences.animal_id = r.animal_id AND last_marked_residences.id_last_residence = r.id
WHERE DATE(r.start_date) <= DATE(a.date_of_death)";

        $this->batchCloseOpenResidencesBase(
            EditTypeEnum::CLOSE_END_DATE_BY_DATE_OF_DEATH,
            $query,
            'Residences closed by matched date of death'
        );
    }


    /**
     * Removed: current animal location_id isNull
     *
     * @param  int|null  $locationId
     * @return int
     */
    private function closeInvalidOpenResidencesByTodayDateForRemovedAnimals(?int $locationId = null): int
    {
        $locationIdFilter = $locationId ? "AND r.location_id = $locationId " : '';
        $sql = "SELECT
                    r.id
                FROM animal_residence r
                         INNER JOIN animal a on r.animal_id = a.id
                         INNER JOIN location lr on r.location_id = lr.id
                WHERE r.is_pending = FALSE AND r.end_date ISNULL
                  $locationIdFilter
                  AND (a.location_id ISNULL OR a.is_alive = FALSE)";

        return $this->closeInvalidOpenResidencesByTodayDateBase(
            $sql,
            EditTypeEnum::CLOSE_END_DATE_BY_CRON_FIX_REMOVED_ANIMAL,
            'for removed animal'
        );
    }

    /**
     * Relocated: current animal location_id isNotNull
     *
     * @param  int|null  $locationId
     * @return int
     */
    private function closeInvalidOpenResidencesByTodayDateForRelocatedAnimals(?int $locationId = null): int
    {
        $locationIdFilter = $locationId ? "AND r.location_id = $locationId " : '';
        $sql = "SELECT
                    r.id
                FROM animal_residence r
                         INNER JOIN animal a on r.animal_id = a.id
                         INNER JOIN location l on a.location_id = l.id
                         INNER JOIN location lr on r.location_id = lr.id
                WHERE r.is_pending = FALSE AND r.end_date ISNULL
                  $locationIdFilter
                  AND a.location_id NOTNULL AND a.location_id <> r.location_id";

        return $this->closeInvalidOpenResidencesByTodayDateBase(
            $sql,
            EditTypeEnum::CLOSE_END_DATE_BY_CRON_FIX_RELOCATED_ANIMAL,
            'for relocated animal'
        );
    }


    /**
     * @param  string  $sqlSelectQueryBase
     * @param  int  $endDateEditTypeEnum
     * @param  string  $logMessageSuffix
     * @return int
     */
    private function closeInvalidOpenResidencesByTodayDateBase(
        string $sqlSelectQueryBase,
        int $endDateEditTypeEnum,
        string $logMessageSuffix = ''
    ): int
    {
        $sqlSelectQuery = $sqlSelectQueryBase."
                    AND NOT EXISTS(
                    -- ARRIVAL FIRST, NO DEPART YET
                
                    -- NO newer animal residence should exist on a new location
                    -- that is still pending and still needs the matching declare depart being processed
                        SELECT
                            *
                        FROM declare_arrival arrival
                                 INNER JOIN declare_base db on arrival.id = db.id
                                 INNER JOIN animal_residence ar on arrival.animal_id = ar.animal_id
                            AND DATE(ar.start_date) = DATE(arrival.arrival_date)
                        WHERE db.request_state IN ('OPEN','FINISHED','FINISHED_WITH_WARNING')
                          AND ar.is_pending
                          AND EXISTS(
                            -- The active location of the previous owner should exist in the NSFO database
                            -- because then a matching declare depart could still be processed
                                SELECT
                                    *
                                FROM declare_depart depart
                                         INNER JOIN declare_base db on depart.id = db.id
                                    AND DATE(ar.start_date) = DATE(depart.depart_date)
                                    AND arrival.animal_id = depart.animal_id
                                    AND db.request_state IN ('OPEN')
                            )
                          AND arrival.animal_id = r.animal_id AND arrival.location_id <> r.location_id
                          AND arrival.arrival_date > r.start_date
                    )
                  AND NOT EXISTS(
                    -- DEPART FIRST NO ARRIVAL YET still being processed
                
                        SELECT
                            *
                        FROM declare_depart depart
                                 INNER JOIN declare_base db on depart.id = db.id
                            AND r.animal_id = depart.animal_id
                            AND r.location_id = depart.location_id
                            AND db.request_state IN ('OPEN')
                            AND DATE(r.start_date) <= DATE(depart.depart_date)
                    )";

        /** @var Employee $automatedProcess */
        $automatedProcess = $this->em->getRepository(Employee::class)->getAutomatedProcess();
        $automatedProcessId = $automatedProcess->getId();

        $updateQuery = "UPDATE animal_residence 
                        SET end_date = NOW(),
                            end_date_edited_by = $automatedProcessId,
                            end_date_edit_type = $endDateEditTypeEnum
                        WHERE EXISTS (
                                $sqlSelectQuery
                                AND r.id = animal_residence.id
                            )";

        $updateCount = SqlUtil::updateWithCount($this->conn, $updateQuery);

        if (empty($updateCount)) {
            $this->logger->info('No ubn history records are updated ' . $logMessageSuffix);
        } else {
            $this->logger->notice($updateCount . ' ubn history records are updated ' . $logMessageSuffix);
        }

        return $updateCount;
    }
}
