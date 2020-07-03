<?php


namespace AppBundle\Service\DataFix;


use AppBundle\Enumerator\EditTypeEnum;
use AppBundle\Util\LoggerUtil;

/**
 * Related to Azure DevOps task 365 Fix invalide UBN historie records gesloten door invalide opvolgende startdatum
 *
 *
 * Class UbnHistoryOneTimeFixer
 * @package AppBundle\Service\DataFix
 */
class UbnHistoryOneTimeFixer extends DuplicateFixerBase
{
    const EDIT_TYPE = EditTypeEnum::DEV_DATABASE_EDIT;
    const EDITED_BY_ID = 2151;


    public function fix()
    {
        $this->fixResidencesPrematurelyClosedWithEditType4Part1PairsOf2();
        $this->fixResidencesPrematurelyClosedWithEditType4Part1SetsOf3(1);
        $this->fixResidencesPrematurelyClosedWithEditType4Part1SetsOf3(2);
        // Run de pairs of 2 fix again after the sets of 3 fix resulted in a few more pairs of 2
        $this->fixResidencesPrematurelyClosedWithEditType4Part1PairsOf2();
    }


    /**
     * Fix all residences pairs of size 2
     */
    public function fixResidencesPrematurelyClosedWithEditType4Part1PairsOf2()
    {
        $sql = "SELECT
                    animal_id,
                    MAX(CASE WHEN residence_ordinal = 1 THEN residence_id END) as residence_id_1,
                    MAX(CASE WHEN residence_ordinal = 2 THEN residence_id END) as residence_id_2,
                    MAX(CASE WHEN residence_ordinal = 1 THEN location_id END) as location_id_1,
                    MAX(CASE WHEN residence_ordinal = 2 THEN location_id END) as location_id_2,
                    MAX(CASE WHEN residence_ordinal = 1 THEN start_date END) as start_date_1,
                    MAX(CASE WHEN residence_ordinal = 2 THEN start_date END) as start_date_2,
                    MAX(CASE WHEN residence_ordinal = 1 THEN end_date END) as end_date_1,
                    MAX(CASE WHEN residence_ordinal = 2 THEN end_date END) as end_date_2,
                    MAX(CASE WHEN residence_ordinal = 1 THEN depart_date END) as depart_date
                FROM (
                     SELECT
                    r.id as residence_id,
                    r.animal_id,
                    DENSE_RANK() OVER (PARTITION BY r.animal_id ORDER BY r.start_date) as residence_ordinal,
                    r.location_id,
                    r.start_date,
                    r.end_date,
                    last_depart.depart_date,
                    r.is_pending,
                    r.start_date_edit_type,
                    r.end_date_edit_type
                FROM (
                    SELECT * FROM animal_residence r
                    WHERE r.is_pending = FALSE AND r.animal_id IN (
                            -- r.end_date_edit_type = 4 ==> 'opvolgende_startdatum*'
                            SELECT r.animal_id FROM animal_residence r WHERE r.end_date_edit_type = 4 GROUP BY r.animal_id
                    )
                )r
                LEFT JOIN (
                        SELECT
                    animal_id,
                    location_id,
                    DATE(max(depart_date)) as depart_date
                FROM declare_depart depart
                    INNER JOIN declare_base db on depart.id = db.id
                WHERE db.request_state IN ('FINISHED','FINISHED_WITH_WARNING','IMPORTED')
                GROUP BY animal_id, location_id
                    )last_depart ON last_depart.animal_id = r.animal_id AND last_depart.location_id = r.location_id
                ORDER BY r.animal_id, r.start_date
                )g
                GROUP BY g.animal_id
                HAVING COUNT(*) = 2 AND
                       -- The residence pairs with a depart date are correct 
                       SUM(CASE WHEN (depart_date NOTNULL) THEN 1 ELSE 0 END) = 0 AND
                       (MAX(location_id) = MIN(location_id)) AND
                       SUM(CASE WHEN (residence_ordinal = 1 AND end_date_edit_type = 4) THEN 1 ELSE 0 END) = 1
                       --SUM(CASE WHEN (residence_ordinal = 2 AND (end_date_edit_type ISNULL OR end_date_edit_type <> 4)) THEN 1 ELSE 0 END) = 1
                ORDER BY MAX(location_id)";
        $results = $this->conn->query($sql)->fetchAll();

        $title = 'Fixing animal residences with editType 4, part 1 set of 2';
        if (empty($results)) {
            $this->logger->notice($title.' - NOT NECESSARY!');
            return;
        }

        $updateCount = 0;

        $this->logger->notice($title.'...');
        $this->logger->notice('starting ...');
        foreach ($results as $result) {
            // Safety check - should not be necessary if the query is correct
            if (
                $result['location_id_1'] !== $result['location_id_2'] ||
                $result['start_date_1'] > $result['start_date_2'] ||
                $result['depart_date'] != null
            ) {
                // Improve query in case we enter this if statement
                $this->logger->error('invalid result!');
                echo $result;
                return;
            }

            $this->updateResidenceEndDate($result['residence_id_1'], $result['end_date_2']);
            $this->deleteResidence($result['residence_id_2']);

            $updateCount++;
            LoggerUtil::overwriteNotice($this->logger, 'Animal residences pairs fixed: '.$updateCount);
        }

        $this->logger->notice('Done!');
    }


    private function updateResidenceEndDate(int $residenceId, ?string $endDate)
    {
        $editType = self::EDIT_TYPE;
        $editedById = self::EDITED_BY_ID;

        $endDateValue = empty($endDate) ? 'NULL' : "'$endDate'";

        $sql = "UPDATE animal_residence 
                            SET end_date = $endDateValue, end_date_edit_type = $editType, end_date_edited_by = $editedById 
                            WHERE id = ".$residenceId;
        $this->conn->exec($sql);
    }


    private function deleteResidence(int $residenceId)
    {
        $sql = "DELETE FROM animal_residence WHERE id = ".$residenceId;
        $this->conn->exec($sql);
    }


    /**
     * Fix all residences pairs of size 3
     *
     * @param  int  $fixNr FIRST run it with "1" then "2"
     * @throws \Doctrine\DBAL\DBALException
     */
    public function fixResidencesPrematurelyClosedWithEditType4Part1SetsOf3(int $fixNr)
    {
        $sql = "SELECT
                    animal_id,
                    MAX(CASE WHEN residence_ordinal = 1 THEN residence_id END) as residence_id_1,
                    MAX(CASE WHEN residence_ordinal = 2 THEN residence_id END) as residence_id_2,
                    MAX(CASE WHEN residence_ordinal = 3 THEN residence_id END) as residence_id_3,
                    MAX(CASE WHEN residence_ordinal = 1 THEN location_id END) as location_id_1,
                    MAX(CASE WHEN residence_ordinal = 2 THEN location_id END) as location_id_2,
                    MAX(CASE WHEN residence_ordinal = 3 THEN location_id END) as location_id_3,
                    MAX(CASE WHEN residence_ordinal = 1 THEN start_date END) as start_date_1,
                    MAX(CASE WHEN residence_ordinal = 2 THEN start_date END) as start_date_2,
                    MAX(CASE WHEN residence_ordinal = 3 THEN start_date END) as start_date_3,
                    MAX(CASE WHEN residence_ordinal = 1 THEN end_date END) as end_date_1,
                    MAX(CASE WHEN residence_ordinal = 2 THEN end_date END) as end_date_2,
                    MAX(CASE WHEN residence_ordinal = 3 THEN end_date END) as end_date_3,
                    MAX(CASE WHEN residence_ordinal = 1 THEN end_date_edit_type END) as end_date_edit_type_1,
                    MAX(CASE WHEN residence_ordinal = 2 THEN end_date_edit_type END) as end_date_edit_type_2,
                    MAX(CASE WHEN residence_ordinal = 3 THEN end_date_edit_type END) as end_date_edit_type_3,
                    MAX(CASE WHEN residence_ordinal = 1 THEN depart_date END) as depart_date
                FROM (
                         SELECT r.id                                                                   as residence_id,
                                r.animal_id,
                                DENSE_RANK() OVER (PARTITION BY r.animal_id ORDER BY r.start_date ASC) as residence_ordinal,
                                r.location_id,
                                r.start_date,
                                r.end_date,
                                last_depart.depart_date,
                                r.is_pending,
                                r.start_date_edit_type,
                                r.end_date_edit_type
                         FROM (
                                  SELECT *
                                  FROM animal_residence r
                                  WHERE r.is_pending = FALSE
                                    AND r.animal_id IN (
                                      -- r.end_date_edit_type = 4 ==> 'opvolgende_startdatum*'
                                      SELECT r.animal_id FROM animal_residence r WHERE r.end_date_edit_type = 4 GROUP BY r.animal_id
                                  )
                                  --AND location_id = 262
                              ) r
                                  LEFT JOIN (
                             SELECT animal_id,
                                    location_id,
                                    DATE(max(depart_date)) as depart_date
                             FROM declare_depart depart
                                      INNER JOIN declare_base db on depart.id = db.id
                             WHERE db.request_state IN ('FINISHED', 'FINISHED_WITH_WARNING', 'IMPORTED')
                             GROUP BY animal_id, location_id
                         ) last_depart ON last_depart.animal_id = r.animal_id AND last_depart.location_id = r.location_id
                         ORDER BY r.animal_id, r.start_date
                     )g
                GROUP BY g.animal_id
                HAVING COUNT(*) = 3 AND
                        SUM(CASE WHEN (depart_date NOTNULL) THEN 1 ELSE 0 END) = 0 AND
                       (MAX(location_id) = MIN(location_id)) AND
                       (SUM(location_id)/COUNT(location_id) = AVG(location_id)) --AND
                       --SUM(CASE WHEN (residence_ordinal < 3 AND end_date_edit_type = 4) THEN 1 ELSE 0 END) = 2
                ORDER BY MAX(location_id)";
        $results = $this->conn->query($sql)->fetchAll();

        $title = 'Fixing animal residences with editType 4, part 1 set of 3 - subPart - '.$fixNr;
        if (empty($results)) {
            $this->logger->notice($title.' - NOT NECESSARY!');
            return;
        }

        $updateCount = 0;
        $skippedCount = 0;

        $this->logger->notice($title.'...');
        $this->logger->notice('starting ...');
        foreach ($results as $result) {
            // Safety check - should not be necessary if the query is correct
            if (
                $result['location_id_1'] !== $result['location_id_2'] ||
                $result['location_id_2'] !== $result['location_id_3'] ||
                $result['start_date_1'] > $result['start_date_2'] ||
                $result['start_date_2'] > $result['start_date_3'] ||
                $result['depart_date'] != null
            ) {
                // Improve query in case we enter this if statement
                $this->logger->error('invalid result!');
                echo $result;
                return;
            }

            if ($fixNr == 1) {
                $updated = $this->removeUnnecessarySandwichedResidenceForSetOf3($result);
            } elseif ($fixNr == 2) {
                $updated = $this->mergeResidencesOfSetOf3Into1($result);
            } else {
                throw new \Exception('Invalid fixNr: '.$fixNr);
            }

            if ($updated) {
                $updateCount++;
            } else {
                $skippedCount++;
            }

            LoggerUtil::overwriteNotice($this->logger, 'Animal residences pairs fixed [skipped|updated]: '.$skippedCount.'|'.$updateCount);
        }

        $this->logger->notice('Done!');
    }

    private function removeUnnecessarySandwichedResidenceForSetOf3(array $result): bool
    {
        $updated = false;
        if (
            $result['end_date_1'] === $result['start_date_2'] &&
            (
                $result['end_date_2'] === $result['start_date_3'] ||
                $result['end_date_2'] < $result['start_date_3']
            ) &&
            $result['location_id_1'] === $result['location_id_2'] &&
            $result['location_id_2'] === $result['location_id_3']
        ) {
            $this->deleteResidence($result['residence_id_2']);
            $updated = true;
        }
        return $updated;
    }


    private function mergeResidencesOfSetOf3Into1(array $result): bool
    {
        $startDates = [$result['start_date_1'], $result['start_date_2'], $result['start_date_3']];
        $finalStartDate = min($startDates);

        $this->updateResidenceStartEndEndDate($result['residence_id_1'], $finalStartDate, $result['end_date_3']);
        $this->deleteResidence($result['residence_id_2']);
        $this->deleteResidence($result['residence_id_3']);

        return true;
    }


    private function updateResidenceStartEndEndDate(int $residenceId, string $startDate, ?string $endDate)
    {
        $editType = self::EDIT_TYPE;
        $editedById = self::EDITED_BY_ID;

        $startDateValue = "'$startDate'";
        $endDateValue = empty($endDate) ? "NULL" : "'$endDate'";

        $sql = "UPDATE animal_residence 
                            SET start_date = $startDateValue,
                                end_date = $endDateValue,
                                end_date_edit_type = $editType, end_date_edited_by = $editedById 
                            WHERE id = ".$residenceId;

        $this->conn->exec($sql);
    }
}
