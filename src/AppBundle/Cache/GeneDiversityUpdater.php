<?php


namespace AppBundle\Cache;


use AppBundle\Constant\ReportLabel;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\HeterosisAndRecombinationUtil;
use AppBundle\Util\NumberUtil;
use AppBundle\Util\SqlUtil;
use Doctrine\DBAL\Connection;

class GeneDiversityUpdater
{
    const BATCH_SIZE = 100000;

    /**
     * @param Connection $conn
     * @param boolean $recalculateAllValues
     * @param CommandUtil $cmdUtil
     * @return int
     */
    public static function updateAll(Connection $conn, $recalculateAllValues = false, $cmdUtil = null)
    {
        $updateCount = 0;

        if($recalculateAllValues) {
            $conn->exec("UPDATE litter SET updated_gene_diversity = FALSE WHERE updated_gene_diversity = TRUE");
            $conn->exec("UPDATE animal SET updated_gene_diversity = FALSE WHERE updated_gene_diversity = TRUE");
        }

        $updateCount += self::updateAnimalsAndLittersWithAMissingParent($conn);
        $updateCount += self::updateAnimalsAndLittersHaveBothParentsWhereBreedCodeIsMissingFromAParent($conn);

        $updateCount += self::updateAllInAnimal($conn, $recalculateAllValues, null, $cmdUtil, false);
        $updateCount += self::updateAllInLitter($conn, $recalculateAllValues, null, $cmdUtil, false);

        $updateCount += self::updateLittersWithPureBredOffspring($conn, false);
        
        if($cmdUtil) { $cmdUtil->writeln('UpdateCount: '.$updateCount); }
        return $updateCount;
    }


    /**
     * @param Connection $conn
     * @param int $parentId
     * @param bool $recalculateAllValues
     * @param CommandUtil $cmdUtil
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function updateByParentId(Connection $conn, $parentId, $recalculateAllValues = true, $cmdUtil = null)
    {
        if(!ctype_digit($parentId) && !is_int($parentId)) { return 0; }

        $updateCount = 0;

        $updateCount += self::updateAnimalsAndLittersWithAMissingParent($conn);
        $updateCount += self::updateAnimalsAndLittersHaveBothParentsWhereBreedCodeIsMissingFromAParent($conn);

        $sql = "SELECT id FROM animal
                WHERE parent_father_id = ".$parentId." OR parent_mother_id = ".$parentId;
        $results = $conn->query($sql)->fetchAll();
        $animalIds = ArrayUtil::get('id', SqlUtil::groupSqlResultsGroupedBySingleVariable('id', $results), []);
        $animalIds[] = $parentId;
        $updateCount += self::updateByAnimalIds($conn, $animalIds, $recalculateAllValues, null, $cmdUtil, false);

        $sql = "SELECT id FROM litter
                WHERE animal_father_id = ".$parentId." OR animal_mother_id = ".$parentId;
        $results = $conn->query($sql)->fetchAll();
        $litterIds = ArrayUtil::get('id', SqlUtil::groupSqlResultsGroupedBySingleVariable('id', $results), []);
        $updateCount += self::updateByLitterIds($conn, $litterIds, $recalculateAllValues, null, $cmdUtil, false);

        $updateCount += self::updateLittersWithPureBredOffspring($conn, false);

        if($cmdUtil) { $cmdUtil->writeln('UpdateCount: '.$updateCount); }

        return $updateCount;
    }


    /**
     * @param Connection $conn
     * @return int
     */
    private static function updateAnimalsAndLittersWithAMissingParent(Connection $conn)
    {
        $filter = ' AND updated_gene_diversity = FALSE ';
        $updateCount = 0;

        //First update values of PURE BRED animals indicated by a 100% breed part in animal and litter
        $sql = "UPDATE animal SET heterosis = 0, recombination = 0, updated_gene_diversity = TRUE
                WHERE substr(breed_code, 3, 3) = '100' AND length(breed_code) = 5
                    AND (parent_father_id ISNULL OR parent_mother_id ISNULL) ";
        $updateCount += SqlUtil::updateWithCount($conn, $sql.$filter);

        $updateCount += self::updateLittersWithPureBredOffspring($conn, true);

        //Then update the values for animals with missing breedcode data
        $sql = "UPDATE animal SET updated_gene_diversity = TRUE, heterosis = NULL, recombination = NULL
                WHERE (NOT(substr(breed_code, 3, 3) = '100' AND length(breed_code) = 5) OR breed_code ISNULL)
                    AND (parent_father_id ISNULL OR parent_mother_id ISNULL) ";
        $updateCount += SqlUtil::updateWithCount($conn, $sql.$filter);

        $sql = "UPDATE litter SET updated_gene_diversity = TRUE, heterosis = NULL, recombination = NULL
                WHERE (animal_father_id ISNULL OR animal_mother_id ISNULL) ";
        $updateCount += SqlUtil::updateWithCount($conn, $sql.$filter);

        return $updateCount;
    }


    /**
     * @param Connection $conn
     * @param bool $onlyIfAParentIsMissing
     * @return int
     */
    private static function updateLittersWithPureBredOffspring(Connection $conn, $onlyIfAParentIsMissing = true)
    {
        $parentFilter = $onlyIfAParentIsMissing ? '' : ' AND (l.animal_mother_id ISNULL OR l.animal_father_id ISNULL) ';

        $sql = "UPDATE litter SET heterosis = 0, recombination = 0, updated_gene_diversity = TRUE
                WHERE id IN (
                  SELECT a.litter_id
                  FROM animal a
                    LEFT JOIN litter l ON l.id = a.litter_id
                  WHERE substr(breed_code, 3, 3) = '100' AND length(a.breed_code) = 5 $parentFilter
                        AND l.heterosis ISNULL OR l.recombination ISNULL AND l.updated_gene_diversity = FALSE 
                  GROUP BY litter_id
                )";
        return SqlUtil::updateWithCount($conn, $sql);
    }


    /**
     * @param Connection $conn
     * @return int
     */
    private static function updateAnimalsAndLittersHaveBothParentsWhereBreedCodeIsMissingFromAParent(Connection $conn)
    {
        $filter = ' AND updated_gene_diversity = FALSE ';
        $updateCount = 0;

        $sql = "UPDATE animal SET updated_gene_diversity = TRUE, heterosis = NULL, recombination = NULL
                WHERE id IN (
                  SELECT c.id FROM animal c
                    INNER JOIN animal f ON f.id = c.parent_father_id
                    INNER JOIN animal m ON m.id = c.parent_mother_id
                  WHERE f.breed_code ISNULL OR m.breed_code ISNULL
                ) ";
        $updateCount += SqlUtil::updateWithCount($conn, $sql.$filter);

        $sql = "UPDATE litter SET updated_gene_diversity = TRUE, heterosis = NULL, recombination = NULL
                WHERE id IN (
                  SELECT l.id FROM litter l
                    INNER JOIN animal f ON f.id = l.animal_father_id
                    INNER JOIN animal m ON m.id = l.animal_mother_id
                  WHERE f.breed_code ISNULL OR m.breed_code ISNULL
                ) ";
        $updateCount += SqlUtil::updateWithCount($conn, $sql.$filter);

        return $updateCount;
    }


    /**
     * @param Connection $conn
     * @param array $animalIds
     * @param bool $recalculateAllValues
     * @param int|null $roundingAccuracy
     * @param CommandUtil $cmdUtil
     * @param boolean $markBlanks
     * @return int
     */
    public static function updateByAnimalIds(Connection $conn, array $animalIds = [], $recalculateAllValues = false, $roundingAccuracy = null, $cmdUtil = null, $markBlanks = true)
    {
        return self::updateByIds('animal', $conn, $animalIds, $recalculateAllValues, $roundingAccuracy, $cmdUtil, $markBlanks);
    }


    /**
     * @param Connection $conn
     * @param array $litterIds
     * @param bool $recalculateAllValues
     * @param int|null $roundingAccuracy
     * @param CommandUtil $cmdUtil
     * @param boolean $markBlanks
     * @return int
     */
    public static function updateByLitterIds(Connection $conn, array $litterIds = [], $recalculateAllValues = false, $roundingAccuracy = null, $cmdUtil = null, $markBlanks = true)
    {
        return self::updateByIds('litter', $conn, $litterIds, $recalculateAllValues, $roundingAccuracy, $cmdUtil, $markBlanks);
    }


    /**
     * @param Connection $conn
     * @param bool $recalculateAllValues
     * @param int|null $roundingAccuracy
     * @param CommandUtil $cmdUtil
     * @param boolean $markBlanks
     * @return int
     */
    public static function updateAllInAnimal(Connection $conn, $recalculateAllValues = false, $roundingAccuracy = null, $cmdUtil = null, $markBlanks = true)
    {
        return self::updateByAnimalIds($conn, [], $recalculateAllValues, $roundingAccuracy, $cmdUtil, $markBlanks);
    }


    /**
     * @param Connection $conn
     * @param bool $recalculateAllValues
     * @param int|null $roundingAccuracy
     * @param CommandUtil $cmdUtil
     * @param boolean $markBlanks
     * @return int
     */
    public static function updateAllInLitter(Connection $conn, $recalculateAllValues = false, $roundingAccuracy = null, $cmdUtil = null, $markBlanks = true)
    {
        return self::updateByLitterIds($conn, [], $recalculateAllValues, $roundingAccuracy, $cmdUtil, $markBlanks);
    }



    /**
     * @param string $tableName
     * @param Connection $conn
     * @param array $ids
     * @param boolean $recalculateAllValues
     * @param int|null $roundingAccuracy
     * @param CommandUtil $cmdUtil
     * @param boolean $markBlanks
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private static function updateByIds($tableName, Connection $conn, array $ids = [], $recalculateAllValues = false, $roundingAccuracy = null, $cmdUtil, $markBlanks = true)
    {
        $markedBlanksUpdateCount = 0;
        if($markBlanks) {
            $markedBlanksUpdateCount += self::updateAnimalsAndLittersWithAMissingParent($conn);
            $markedBlanksUpdateCount += self::updateAnimalsAndLittersHaveBothParentsWhereBreedCodeIsMissingFromAParent($conn);
            if($cmdUtil) { $cmdUtil->writeln($markedBlanksUpdateCount.' blanks marked/updated'); }
        }


        if(count($ids) > 0) {
            $idFilterString = '('.SqlUtil::getFilterStringByIdsArray($ids, 'c.id').')';
            if($recalculateAllValues) {
                $filter = ' WHERE '.$idFilterString;
            } else {
                $filter = ' WHERE c.updated_gene_diversity = FALSE AND '.$idFilterString;
            }
        } else {
            if($recalculateAllValues) {
                $filter = '';
            } else {
                $filter = ' WHERE c.updated_gene_diversity = FALSE ';
            }
        }

        switch ($tableName) {
            case 'animal':
                $sql = "SELECT c.id, c.heterosis, c.recombination, f.breed_code as breed_code_father, m.breed_code as breed_code_mother,
                                substr(c.breed_code, 3, 3) = '100' AND length(c.breed_code) = 5 as is_pure_bred
                FROM animal c
                  LEFT JOIN animal f ON f.id = c.parent_father_id
                  LEFT JOIN animal m ON m.id = c.parent_mother_id 
                  ".$filter." 
                ORDER BY c.date_of_birth ASC";
                break;

            case 'litter':
                $sql = "SELECT c.id, c.heterosis, c.recombination, f.breed_code as breed_code_father,
                                m.breed_code as breed_code_mother, is_pure_bred
                FROM litter c
                  LEFT JOIN animal f ON f.id = c.animal_father_id
                  LEFT JOIN animal m ON m.id = c.animal_mother_id 
                  LEFT JOIN (
                      SELECT a.litter_id, substr(min(a.breed_code), 3, 3) = '100' AND length(min(a.breed_code)) = 5 as is_pure_bred
                        FROM animal a
                          LEFT JOIN litter l ON l.id = a.litter_id
                        GROUP BY litter_id
                  )offspring ON offspring.litter_id = c.id
                  ".$filter." 
                ORDER BY c.litter_date ASC";
                break;

            default:
                if($cmdUtil) { $cmdUtil->writeln('Invalid table name used for updating gene diversity'); }
                return 0;
        }

        $results = $conn->query($sql)->fetchAll();

        $updateString = '';
        $updateStringPrefix = '';
        $idsUpdateArray = [];

        $totalCount = count($results);
        $loopCount = 0;
        $toUpdateCount = 0;
        $updatedCount = 0;
        $newValueCount = 0;
        $unchangedValueCount = 0;

        if($cmdUtil) { $cmdUtil->setStartTimeAndPrintIt(ceil($totalCount/self::BATCH_SIZE)+1, 1); }

        foreach ($results as $result) {
            $loopCount++;
            $id = $result['id'];
            $breedCodeStringFather = $result['breed_code_father'];
            $breedCodeStringMother = $result['breed_code_mother'];
            $currentHeterosis = $result['heterosis'];
            $currentRecombination = $result['recombination'];
            $isPureBred = $result['is_pure_bred'];
            $geneDiversityValues = HeterosisAndRecombinationUtil::getHeterosisAndRecombinationBy8Parts($breedCodeStringFather, $breedCodeStringMother, $roundingAccuracy);

            $currentAndNewAreAllNull = false;
            if($geneDiversityValues != null) {
                $heterosisValue = ArrayUtil::get(ReportLabel::HETEROSIS, $geneDiversityValues);
                $recombinationValue = ArrayUtil::get(ReportLabel::RECOMBINATION, $geneDiversityValues);
            } elseif($isPureBred) {
                $heterosisValue = 0;
                $recombinationValue = 0;
            } else {
                $heterosisValue = 'NULL';
                $recombinationValue = 'NULL';
                $currentAndNewAreAllNull = $currentHeterosis === null && $currentRecombination === null;
            }

            $numbersAreEqual = NumberUtil::areFloatsEqual($currentHeterosis, $heterosisValue) && NumberUtil::areFloatsEqual($currentRecombination, $recombinationValue);

            if($numbersAreEqual || $currentAndNewAreAllNull) {
                $idsUpdateArray[] = $id;
                $toUpdateCount++;
                $unchangedValueCount++;
            } else {
                $updateString = $updateString.$updateStringPrefix.'('.$id.','.$heterosisValue.','.$recombinationValue.')';
                $updateStringPrefix = ',';
                $toUpdateCount++;
                $newValueCount++;
            }

            if($toUpdateCount >= self::BATCH_SIZE || $loopCount >= $totalCount) {
                $updatedCount += self::updateGeneticDiversityValuesByUpdateString($conn, $tableName, $updateString);
                $updatedCount += self::setGeneticDiversityIsTrueByAnimalIds($conn, $tableName, $idsUpdateArray);
                //Reset values
                $toUpdateCount = 0;
                $updateString = '';
                $updateStringPrefix = '';
                $idsUpdateArray = [];

                if($cmdUtil) { $cmdUtil->advanceProgressBar(1, $updatedCount.'/'.$totalCount.' '.$tableName.'s have updated heterosis and recombination values, new|unchanged: '
                    .$newValueCount.'|'.$unchangedValueCount); }
            }
        }
        if($cmdUtil) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }

        if($markBlanks) {
            $markedBlanksUpdateCount += self::updateLittersWithPureBredOffspring($conn, false);
            if($cmdUtil) { $cmdUtil->writeln($markedBlanksUpdateCount.' blanks marked/updated'); }
        }

        return $updatedCount + $markedBlanksUpdateCount;
    }


    /**
     * @param Connection $conn
     * @param string $tableName
     * @param string $updateString
     * @return int
     */
    private static function updateGeneticDiversityValuesByUpdateString(Connection $conn, $tableName, $updateString)
    {
        if(trim($updateString) == '') { return 0; }
        if(!is_string($tableName)) { return 0; }

        $sql = "UPDATE $tableName
                    SET heterosis = v.heterosis, recombination = v.recombination,
                        updated_gene_diversity = TRUE
						FROM ( VALUES ".$updateString."
							 ) as v(id, heterosis, recombination) WHERE $tableName.id = v.id";
        return SqlUtil::updateWithCount($conn, $sql);
    }


    /**
     * @param Connection $conn
     * @param string $tableName
     * @param array $ids
     * @return int
     */
    public static function setGeneticDiversityIsTrueByAnimalIds(Connection $conn, $tableName, array $ids = [])
    {
        if(!is_array($ids)) { return 0; }
        if(count($ids) == 0) { return 0; }
        if(!is_string($tableName)) { return 0; }
        
        $filterString = implode(',', $ids);
        $sql = "UPDATE $tableName SET updated_gene_diversity = TRUE
                WHERE id IN (".$filterString.")";
        return SqlUtil::updateWithCount($conn, $sql);
    }


}