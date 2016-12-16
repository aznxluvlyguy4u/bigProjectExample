<?php

namespace AppBundle\Util;


use AppBundle\Constant\MeasurementConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Weight;
use AppBundle\Enumerator\ExteriorKind;
use AppBundle\Enumerator\MeasurementType;
use AppBundle\Enumerator\WeightType;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\Validator\Constraints\Time;

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


    /**
     * @param float $weightValue
     * @return bool
     */
    public static function isValidBirthWeightValue($weightValue)
    {
        return $weightValue <= 10.0;
    }


    /**
     * @param float $muscleThickness
     * @param boolean $hasValid20WeeksWeightMeasurement
     * @return bool
     */
    public static function isValidMuscleThicknessValue($muscleThickness, $hasValid20WeeksWeightMeasurement)
    {
        return $muscleThickness >= MeasurementConstant::MUSCLE_THICKNESS_MIN_VALUE && $muscleThickness <= MeasurementConstant::MUSCLE_THICKNESS_MAX_VALUE && $hasValid20WeeksWeightMeasurement;
    }


    /**
     * @param float $fatValue
     * @param boolean $hasValid20WeeksWeightMeasurement
     * @return bool
     */
    public static function isValidFatValue($fatValue, $hasValid20WeeksWeightMeasurement)
    {
        return $fatValue >= MeasurementConstant::FAT_MIN_VALUE && $fatValue <= MeasurementConstant::FAT_MAX_VALUE && $hasValid20WeeksWeightMeasurement;
    }


    /**
     * @param float $fat1
     * @param float $fat2
     * @param float $fat3
     * @param boolean $hasValid20WeeksWeightMeasurement
     * @return bool
     */
    public static function isValidBodyFatValues($fat1, $fat2, $fat3, $hasValid20WeeksWeightMeasurement)
    {
        return self::isValidFatValue($fat1, $hasValid20WeeksWeightMeasurement) 
            && self::isValidFatValue($fat2, $hasValid20WeeksWeightMeasurement) 
            && self::isValidFatValue($fat3, $hasValid20WeeksWeightMeasurement);
    }


    /**
     * @param int $ageAtMeasurement
     * @return null|string
     */
    public static function getWeightType($ageAtMeasurement)
    {
        if(!is_int($ageAtMeasurement)) { return null; }

        if(MeasurementConstant::BIRTH_WEIGHT_MIN_AGE <= $ageAtMeasurement && $ageAtMeasurement <= MeasurementConstant::BIRTH_WEIGHT_MAX_AGE) {
            return WeightType::BIRTH;

        } elseif(MeasurementConstant::WEIGHT_AT_8_WEEKS_MIN_AGE <= $ageAtMeasurement && $ageAtMeasurement <= MeasurementConstant::WEIGHT_AT_8_WEEKS_MAX_AGE) {
            return WeightType::EIGHT_WEEKS;

        } elseif(MeasurementConstant::WEIGHT_AT_20_WEEKS_MIN_AGE <= $ageAtMeasurement && $ageAtMeasurement <= MeasurementConstant::WEIGHT_AT_20_WEEKS_MAX_AGE) {
            return WeightType::TWENTY_WEEKS;

        } else {
            return null;
        }
    }


    /**
     * @param int $ageAtMeasurement
     * @param float $weight
     * @return bool
     */
    public static function isValidMixblupWeight($ageAtMeasurement, $weight)
    {
        if(!is_int($ageAtMeasurement)) { return false; }
        if($weight < 0) { return false; }

        switch (self::getWeightType($ageAtMeasurement)) {
            case WeightType::BIRTH:
                if(MeasurementConstant::BIRTH_WEIGHT_MIN_VALUE <= $weight && $weight <= MeasurementConstant::BIRTH_WEIGHT_MAX_VALUE) {
                    return true;
                } else {
                    return false;
                }
                    
            case WeightType::EIGHT_WEEKS:
                if(MeasurementConstant::WEIGHT_AT_8_WEEKS_MIN_VALUE <= $weight && $weight <= MeasurementConstant::WEIGHT_AT_8_WEEKS_MAX_VALUE) {
                    return true;
                } else {
                    return false;
                }

            case WeightType::TWENTY_WEEKS:
                if(MeasurementConstant::WEIGHT_AT_20_WEEKS_MIN_VALUE <= $weight && $weight <= MeasurementConstant::WEIGHT_AT_20_WEEKS_MAX_VALUE) {
                    return true;
                } else {
                    return false;
                }

            default:
                return false;
        }
    }


    /**
     * @param ObjectManager $em
     * @param Animal $animal
     * @param boolean $filterByAnimalData
     * @return array
     */
    public static function getExteriorKindsOutput(ObjectManager $em, Animal $animal, $filterByAnimalData = true)
    {
        $output = [];

        if(!$filterByAnimalData) {
            foreach (ExteriorKind::getAll() as $kind) { $output[] = ['code' => $kind]; }
            return $output;
        }

        /** @var Connection $conn */
        $conn = $em->getConnection();

        //Create Kind searchArray
        $sql = "SELECT kind FROM measurement m
                    INNER JOIN exterior x ON x.id = m.id
                    WHERE animal_id = 109
                ORDER BY measurement_date DESC";
        $results = $conn->query($sql)->fetchAll();

        $latestKind = null;
        $kinds = [];
        $isLatestKind = true;
        foreach ($results as $result) {            
            $kind = $result['kind'];
            if(NullChecker::isNull($kind)) { $kind = null; }
            
            $kinds[$kind] = $kind;
            
            if($isLatestKind) {
                $latestKind = $kind;
                $isLatestKind = false;
            }
        }
        
        $vgExists = array_key_exists(ExteriorKind::VG_, $kinds);
        $ddExists = array_key_exists(ExteriorKind::DD_, $kinds);
        $dfExists = array_key_exists(ExteriorKind::DF_, $kinds);
        $hkExists = array_key_exists(ExteriorKind::HK_, $kinds);
        $hhExists = array_key_exists(ExteriorKind::HH_, $kinds);


        $ageInMonths = TimeUtil::getAgeMonths($animal->getDateOfBirth(), $animal->getDateOfDeath());
        $ageIsBetween5and14months = 4 <= $ageInMonths && $ageInMonths <= 14;
        $ageIsBetween14and26months = 14 <= $ageInMonths && $ageInMonths <= 26;
        $ageIsAtLeast26months = 26 <= $ageInMonths;

        if($ageIsBetween5and14months) {
            $output[] = ['code' => ExteriorKind::VG_];

        } elseif($ageIsBetween14and26months) {
            if($vgExists) {
                $output[] = ['code' => ExteriorKind::DF_];
            } else {
                $output[] = ['code' => ExteriorKind::DD_];
            }

        } elseif($ageIsAtLeast26months && ($ddExists || $dfExists
            || $hkExists || $hhExists //adding $hkExists && $hhExists in case of incomplete exterior data
            )) {
            $output[] = ['code' => ExteriorKind::HH_];
        }

        if(!$animal->getIsAlive()) { $output[] = ['code' => ExteriorKind::DO_]; }

        if($ddExists || $dfExists || $vgExists
            || $hkExists || $hhExists //adding $hkExists && $hhExists in case of incomplete exterior data
        ) { $output[] = ['code' => ExteriorKind::HK_]; }
        
        return $output;
    }


    /**
     * @param ObjectManager $em
     * @param Animal $animal
     * @param boolean $filterByAnimalData
     * @return array
     */
    public static function getExteriorKinds(ObjectManager $em, Animal $animal, $filterByAnimalData = true) {
        $output = self::getExteriorKindsOutput($em, $animal, $filterByAnimalData);

        $codes = [];
        foreach ($output as $item) {
            $code = $item['code'];
            $codes[$code] = $code;
        }
        return $codes;
    }
}