<?php

namespace AppBundle\Util;


use AppBundle\Constant\MeasurementConstant;
use AppBundle\Enumerator\MeasurementType;
use Doctrine\Common\Persistence\ObjectManager;

class MeasurementsUtil
{
    const MUSCLE_THICKNESS_TABLE_NAME = 'muscle_thickness';
    const WEIGHT_TABLE_NAME = 'weight';
    const TAIL_LENGTH_TABLE_NAME = 'tail_length';
    const EXTERIOR_TABLE_NAME = 'exterior';
    const BODY_FAT_TABLE_NAME = 'body_fat';


    /**
     * @param ObjectManager $em
     * @return int
     */
    public static function getEmptyAnimalIdAndDateCount(ObjectManager $em)
    {
        $sql = "SELECT COUNT(*) FROM measurement WHERE (animal_id_and_date ISNULL OR animal_id_and_date = '') AND type <> 'Fat1' AND type <> 'Fat2' AND type <>'Fat3'";
        $result = $em->getConnection()->query($sql)->fetch()['count'];
        return $result;
    }
    
    
    /**
     * @param ObjectManager $em
     * @param bool $isRegenerateFilledValues
     * @return int
     */
    public static function generateAnimalIdAndDateValues(ObjectManager $em, $isRegenerateFilledValues = false) {

        $count  = self::generateAnimalIdAndDateValuesForType($em, self::BODY_FAT_TABLE_NAME, $isRegenerateFilledValues);
        $count += self::generateAnimalIdAndDateValuesForType($em, self::MUSCLE_THICKNESS_TABLE_NAME, $isRegenerateFilledValues);
        $count += self::generateAnimalIdAndDateValuesForType($em, self::WEIGHT_TABLE_NAME, $isRegenerateFilledValues);
        $count += self::generateAnimalIdAndDateValuesForType($em, self::TAIL_LENGTH_TABLE_NAME, $isRegenerateFilledValues);
        $count += self::generateAnimalIdAndDateValuesForType($em, self::EXTERIOR_TABLE_NAME, $isRegenerateFilledValues);

        return $count;
    }

    /**
     * @param ObjectManager $em
     * @param $tableName
     * @param bool $isRegenerateFilledValues
     * @return int
     */
    private static function generateAnimalIdAndDateValuesForType(ObjectManager $em, $tableName, $isRegenerateFilledValues = false)
    {
        if($isRegenerateFilledValues) {
            $sqlFilter = '';
        } else {
            $sqlFilter = "WHERE m.animal_id_and_date ISNULL OR m.animal_id_and_date = ''";
        }

        $sql = "SELECT m.id, CONCAT(t.animal_id,'_',DATE(measurement_date)) as code FROM measurement m INNER JOIN ".$tableName." t ON m.id = t.id ".$sqlFilter."";
        $results = $em->getConnection()->query($sql)->fetchAll();

        foreach ($results as $result) {
            $id = intval($result['id']);
            $code = $result['code'];
            $sql = "UPDATE measurement SET animal_id_and_date = '".$code."' WHERE id = ".$id;
            $em->getConnection()->exec($sql);
        }
        
        return count($results);
    }


    /**
     * @param string $animalIdAndDate
     * @return array
     */
    public static function getIdAndDateFromAnimalIdAndDateString($animalIdAndDate)
    {
        $parts = explode('_', $animalIdAndDate);
        return [
            MeasurementConstant::ANIMAL_ID => $parts[0],
            MeasurementConstant::DATE => $parts[1]
        ];
    }


    /**
     * @param $type
     * @return boolean
     */
    public static function isValidMeasurementType($type)
    {
        return array_key_exists($type, MeasurementType::getTypes());
    }
}