<?php

namespace AppBundle\Migration;

use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Util\SqlUtil;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class BreedValuesSetMigrator
 * @package AppBundle\Migration
 */
class BreedValuesSetMigrator
{
    const GENERATION_DATE_STRING = '2016-10-04 00:00:00';
    const LAMB_MEAT_INDEX_TYPE = 'LambMeat';

    const VALUE_VAR = 'valueVar';
    const RELIABILITY_VAR = 'reliabilityVar';
    const DUTCH_BREED_VALUE_TYPE = 'breedValueTypeNl';

    /**
     * @param Connection $conn
     * @param Logger $logger
     * @return int
     */
    public static function migrate(Connection $conn, Logger $logger)
    {
        $insertCount = 0;
        $insertCount += self::migrateLambMeatIndices($conn, $logger);
        $insertCount += self::migrateBreedValues($conn, $logger);

        $prefix = $insertCount > 0 ? $insertCount : 'No';
        $logger->notice($prefix. ' breedValueSet values inserted in total');

        return $insertCount;
    }


    /**
     * @param Connection $conn
     * @param Logger $logger
     * @return int
     */
    private static function migrateLambMeatIndices(Connection $conn, Logger $logger)
    {
        $logger->notice('Migrate lambMeatIndex records from breed_values_set to breed_index table ...');

        $sql = "INSERT INTO breed_index (animal_id, log_date, generation_date, index, accuracy, ranking, type)
                  SELECT s.animal_id, s.log_date, s.generation_date, lamb_meat_index, lamb_meat_index_accuracy,
                         lamb_meat_index_ranking, '".self::LAMB_MEAT_INDEX_TYPE."' as type
                  FROM breed_values_set s
                    LEFT JOIN (
                                SELECT i.id as breed_index_id, i.animal_id as index_animal_id,
                                       i.log_date as index_log_date, i.generation_date as index_generation_date,
                                       i.index as index_value, i.accuracy as index_accuracy
                                FROM breed_index i
                                WHERE type = '".self::LAMB_MEAT_INDEX_TYPE."'
                              )b ON b.index_animal_id = s.animal_id AND b.index_generation_date = s.generation_date
                  WHERE s.generation_date = '".self::GENERATION_DATE_STRING."'
                        AND lamb_meat_index_accuracy > 0
                        AND b.breed_index_id ISNULL";
        $insertCount = SqlUtil::updateWithCount($conn, $sql);

        $childTableInsertCount = 0;
        if($insertCount > 0) {
            $logger->notice('Matching lamb_meat_breed_index records with inserted breed_index records ...');

            $sql = "INSERT INTO lamb_meat_breed_index (id)
                  SELECT i.id
                  FROM breed_index i
                    LEFT JOIN lamb_meat_breed_index c ON i.id = c.id
                  WHERE type = '".self::LAMB_MEAT_INDEX_TYPE."' AND c.id ISNULL";
            $childTableInsertCount = SqlUtil::updateWithCount($conn, $sql);
        }

        if($insertCount != $childTableInsertCount) {
            $logger->warning('insertCount not equal by '.$insertCount - $childTableInsertCount.' records! Check');
        } elseif ($insertCount > 0) {
            $logger->warning('lamb_meat_breed_index records matched with inserted breed_index records');
        }

        $prefix = $insertCount > 0 ? $insertCount : 'No';
        $logger->notice($prefix. ' lambMeatIndex records inserted');

        return $insertCount;
    }


    /**
     * @param Connection $conn
     * @param Logger $logger
     * @return int
     */
    private static function migrateBreedValues(Connection $conn, Logger $logger)
    {
        $updateSets = [];

        $updateSets[] = [
            self::VALUE_VAR => 'muscle_thickness',
            self::RELIABILITY_VAR => 'muscle_thickness_reliability',
            self::DUTCH_BREED_VALUE_TYPE => BreedValueTypeConstant::MUSCLE_THICKNESS,
        ];

        $updateSets[] = [
            self::VALUE_VAR => 'growth',
            self::RELIABILITY_VAR => 'growth_reliability',
            self::DUTCH_BREED_VALUE_TYPE => BreedValueTypeConstant::GROWTH,
        ];

        $updateSets[] = [
            self::VALUE_VAR => 'fat',
            self::RELIABILITY_VAR => 'fat_reliability',
            self::DUTCH_BREED_VALUE_TYPE => BreedValueTypeConstant::FAT_THICKNESS_3,
        ];

        $insertCount = 0;

        foreach ($updateSets as $updateSet) {

            $valueVar = $updateSet[self::VALUE_VAR];
            $reliabilityVar = $updateSet[self::RELIABILITY_VAR];
            $breedValueTypeNl = $updateSet[self::DUTCH_BREED_VALUE_TYPE];

            $logger->notice('insert '.$breedValueTypeNl.' records from breed_values_set into breed_value table ...');

            $sql = "INSERT INTO breed_value (animal_id, type_id, log_date, generation_date, value, reliability)
                  SELECT s.animal_id, t.id as type_id, s.log_date, s.generation_date, s.$valueVar, s.$reliabilityVar
                  FROM breed_values_set s
                    LEFT JOIN (
                                SELECT b.*
                                FROM breed_value b
                                  INNER JOIN breed_value_type t ON b.type_id = t.id
                                WHERE t.nl = '$breedValueTypeNl'
                              )v ON v.animal_id = s.animal_id AND v.generation_date = s.generation_date
                    LEFT JOIN (
                                SELECT id FROM breed_value_type WHERE nl = '$breedValueTypeNl'
                              )t ON TRUE
                  WHERE s.generation_date = '".self::GENERATION_DATE_STRING."'
                        AND s.$reliabilityVar > 0
                        AND v.id ISNULL";
            $insertCount += SqlUtil::updateWithCount($conn, $sql);

            $prefix = $insertCount > 0 ? $insertCount : 'No';
            $logger->notice($prefix. ' '.$breedValueTypeNl.' records inserted');
        }

        return $insertCount;
    }


}