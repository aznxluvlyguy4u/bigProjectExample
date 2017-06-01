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
    const UPDATE_FILTER = "updated_gene_diversity = FALSE ";
    const BATCH_SIZE = 100000;

    /**
     * @param Connection $conn
     * @param array $animalIds
     * @param boolean $recalculateAllValues
     * @param CommandUtil $cmdUtil
     * @return int
     */
    public static function update(Connection $conn, array $animalIds = [], $recalculateAllValues = false, $cmdUtil = null)
    {
        $updateCount = 0;
        if(count($animalIds) == 0) {
            if($cmdUtil) { $cmdUtil->setStartTimeAndPrintIt(3, 1, 'Updating heterosis and recombination values'); }
            $updateCount += self::updateAnimalsAndLittersWithAMissingParent($conn, $recalculateAllValues);
            if($cmdUtil) { $cmdUtil->advanceProgressBar(1, '(1/3) updated_gene_diversity = TRUE has been set'); }
            $updateCount += self::updateAnimalsAndLittersHaveBothParentsWhereBreedCodeIsMissingFromAParent($conn, $recalculateAllValues);
            if($cmdUtil) { $cmdUtil->advanceProgressBar(1, '(2/3) updated_gene_diversity = TRUE has been set'); }
            if($cmdUtil) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }
        }

        $updateCount += self::updateByAnimalIds($conn, $animalIds, $recalculateAllValues, null, $cmdUtil);
        if($cmdUtil) { $cmdUtil->writeln('Total updateCount: '.$updateCount); }
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
        $sql = "SELECT id FROM animal
                WHERE parent_father_id = ".$parentId." OR parent_mother_id = ".$parentId;
        $results = $conn->query($sql)->fetchAll();
        $animalIds = SqlUtil::groupSqlResultsGroupedBySingleVariable('id', $results)['id'];
        $animalIds[] = $parentId;
        return self::update($conn, $animalIds, $recalculateAllValues, $cmdUtil);
    }


    /**
     * @param Connection $conn
     * @param boolean $recalculateAllValues
     * @return int
     */
    private static function updateAnimalsAndLittersWithAMissingParent(Connection $conn, $recalculateAllValues = false)
    {
        $filter = $recalculateAllValues ? ' ' : ' AND '.self::UPDATE_FILTER;
        $updateCount = 0;

        $sql = "UPDATE animal SET updated_gene_diversity = TRUE, heterosis = NULL, recombination = NULL
                WHERE (parent_father_id ISNULL OR parent_mother_id ISNULL) ";
        $updateCount += SqlUtil::updateWithCount($conn, $sql.$filter);

        $sql = "UPDATE litter SET updated_gene_diversity = TRUE, heterosis = NULL, recombination = NULL
                WHERE (animal_father_id ISNULL OR animal_mother_id ISNULL) ";
        $updateCount += SqlUtil::updateWithCount($conn, $sql.$filter);

        return $updateCount;
    }


    /**
     * @param Connection $conn
     * @param boolean $recalculateAllValues
     * @return int
     */
    private static function updateAnimalsAndLittersHaveBothParentsWhereBreedCodeIsMissingFromAParent(Connection $conn, $recalculateAllValues = false)
    {
        $filter = $recalculateAllValues ? ' ' : ' AND '.self::UPDATE_FILTER;
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
     * @param boolean $recalculateAllValues
     * @param int|null $roundingAccuracy
     * @param CommandUtil $cmdUtil
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private static function updateByAnimalIds(Connection $conn, array $animalIds = [], $recalculateAllValues = false, $roundingAccuracy = null, $cmdUtil)
    {
        if(count($animalIds) > 0) {
            $animalIdFilterString = '('.SqlUtil::getFilterStringByIdsArray($animalIds, 'c.id').')';
            if($recalculateAllValues) {
                $filter = ' WHERE '.$animalIdFilterString;
            } else {
                $filter = ' WHERE c.'.self::UPDATE_FILTER.' AND '.$animalIdFilterString;
            }
        } else {
            if($recalculateAllValues) {
                $filter = '';
            } else {
                $filter = ' WHERE c.'.self::UPDATE_FILTER;
            }
        }


        $sql = "SELECT c.id, c.heterosis, c.recombination, f.breed_code as breed_code_father, m.breed_code as breed_code_mother
                FROM animal c
                  LEFT JOIN animal f ON f.id = c.parent_father_id
                  LEFT JOIN animal m ON m.id = c.parent_mother_id 
                  ".$filter." 
                ORDER BY c.date_of_birth ASC";
        $results = $conn->query($sql)->fetchAll();

        $updateString = '';
        $updateStringPrefix = '';
        $animalIdsUpdateArray = [];

        $totalCount = count($results);
        $loopCount = 0;
        $toUpdateCount = 0;
        $updatedCount = 0;
        $newValueCount = 0;
        $unchangedValueCount = 0;

        if($cmdUtil) { $cmdUtil->setStartTimeAndPrintIt(ceil($totalCount/self::BATCH_SIZE)+1, 1); }

        foreach ($results as $result) {
            $loopCount++;
            $animalId = $result['id'];
            $breedCodeStringFather = $result['breed_code_father'];
            $breedCodeStringMother = $result['breed_code_mother'];
            $currentHeterosis = $result['heterosis'];
            $currentRecombination = $result['recombination'];
            $geneDiversityValues = HeterosisAndRecombinationUtil::getHeterosisAndRecombinationBy8Parts($breedCodeStringFather, $breedCodeStringMother, $roundingAccuracy);
            $heterosisValue = $geneDiversityValues != null ? ArrayUtil::get(ReportLabel::HETEROSIS, $geneDiversityValues) : 'NULL';
            $recombinationValue = $geneDiversityValues != null ? ArrayUtil::get(ReportLabel::RECOMBINATION, $geneDiversityValues) : 'NULL';

            if(NumberUtil::areFloatsEqual($currentHeterosis, $heterosisValue) && NumberUtil::areFloatsEqual($currentRecombination, $recombinationValue)) {
                $animalIdsUpdateArray[] = $animalId;
                $toUpdateCount++;
                $unchangedValueCount++;
            } else {
                $updateString = $updateString.$updateStringPrefix.'('.$animalId.','.$heterosisValue.','.$recombinationValue.')';
                $updateStringPrefix = ',';
                $toUpdateCount++;
                $newValueCount++;
            }

            if($toUpdateCount >= self::BATCH_SIZE || $loopCount >= $totalCount) {
                $updatedCount += self::updateGeneticDiversityValuesByUpdateString($conn, $updateString);
                $updatedCount += self::setGeneticDiversityIsTrueByAnimalIds($conn, $animalIdsUpdateArray);
                //Reset values
                $toUpdateCount = 0;
                $updateString = '';
                $updateStringPrefix = '';
                $animalIdsUpdateArray = [];

                if($cmdUtil) { $cmdUtil->advanceProgressBar(1, $updatedCount.'/'.$totalCount.' animals have updated heterosis and recombination values, new|unchanged: '
                    .$newValueCount.'|'.$unchangedValueCount); }
            }
        }
        if($cmdUtil) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }

        return $updatedCount;
    }


    /**
     * @param Connection $conn
     * @param $updateString
     * @return int
     */
    private static function updateGeneticDiversityValuesByUpdateString(Connection $conn, $updateString)
    {
        if(trim($updateString) == '') { return 0; }
        $sql = "UPDATE animal
                    SET heterosis = v.heterosis, recombination = v.recombination,
                        updated_gene_diversity = TRUE
						FROM ( VALUES ".$updateString."
							 ) as v(animal_id, heterosis, recombination) WHERE id = v.animal_id";
        return SqlUtil::updateWithCount($conn, $sql);
    }


    /**
     * @param Connection $conn
     * @param array $animalIds
     * @return int
     */
    public static function setGeneticDiversityIsTrueByAnimalIds(Connection $conn, array $animalIds = [])
    {
        if(!is_array($animalIds)) { return 0; }
        if(count($animalIds) == 0) { return 0; }
        
        $filterString = implode(',', $animalIds);
        $sql = "UPDATE animal SET updated_gene_diversity = TRUE
                WHERE id IN (".$filterString.")";
        return SqlUtil::updateWithCount($conn, $sql);
    }

    
}