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
    /**
     * @param Connection $conn
     * @param bool $isRegenerateFilledValues
     * @return int
     */
    public static function generateAnimalIdAndDateValues(Connection $conn, $isRegenerateFilledValues = false)
    {
        $sqlFilter = $isRegenerateFilledValues ? '' : "WHERE m.animal_id_and_date ISNULL OR m.animal_id_and_date = ''";

        $tableNames = ['muscle_thickness', 'weight', 'tail_length', 'exterior', 'body_fat'];

        $sqlSelect = '';
        foreach ($tableNames as $key => $tableName) {
            $sqlSelect = $sqlSelect
                ."SELECT m.id, CONCAT(t.animal_id,'_',DATE(measurement_date))
                    FROM measurement m
                        INNER JOIN ".$tableName." t ON m.id = t.id 
                        ".$sqlFilter;

            if($key < count($tableNames) - 1) {
                $sqlSelect = $sqlSelect."
                 
                 UNION ";
            }
        }

        $sql = "WITH rows AS (
                UPDATE measurement as mm SET animal_id_and_date = v.animal_id_and_date
                FROM (
                    $sqlSelect
                ) as v(measurement_id, animal_id_and_date) WHERE mm.id = v.measurement_id
                RETURNING 1
                )
                SELECT COUNT(*) AS count FROM rows";

        return $results = $conn->query($sql)->fetch()['count'];
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
     * @param string $currentKind
     * @param boolean $filterByAnimalData
     * @return array
     */
    public static function getExteriorKinds(ObjectManager $em, Animal $animal, $currentKind = null, $filterByAnimalData = true) {
        $output = self::getExteriorKindsOutput($em, $animal, $currentKind, $filterByAnimalData);

        $codes = [];
        foreach ($output as $item) {
            $code = $item['code'];
            $codes[$code] = $code;
        }
        return $codes;
    }
    
    
    /**
     * @param ObjectManager $em
     * @param Animal $animal
     * @param string $currentKind
     * @param boolean $filterByAnimalData
     * @return array
     */
    public static function getExteriorKindsOutput(ObjectManager $em, Animal $animal, $currentKind = null, $filterByAnimalData = true)
    {
        //TODO filter kinds based on previous ACTIVE exteriors AND age on measurementDate. For now just return all exteriorKinds
        $kindsForOutput = ExteriorKind::getAll();

        sort($kindsForOutput);
        foreach ($kindsForOutput as $kind) {
            $output[] = ['code' => $kind];
        }

        return $output;

        /* */

        $output = [];

        if(!$filterByAnimalData) {
            foreach (ExteriorKind::getAll() as $kind) { $output[] = ['code' => $kind]; }
            return $output;
        }

        $animalId = null;
        if($animal) {
            $animalId = $animal->getId();
            if(!is_int($animalId)) {
                return $output;
            }
        }

        /** @var Connection $conn */
        $conn = $em->getConnection();

        //Create Kind searchArray
        $sql = "SELECT kind FROM measurement m
                    INNER JOIN exterior x ON x.id = m.id
                    WHERE animal_id = ".$animalId." AND m.is_active = TRUE
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

        $kindsForOutput = [];
        if($ageIsBetween5and14months) {
            $kindsForOutput[] = ExteriorKind::VG_;

        } elseif($ageIsBetween14and26months) {
            if($vgExists) {
                $kindsForOutput[] = ExteriorKind::DF_;
            } else {
                $kindsForOutput[] = ExteriorKind::DD_;
            }

        } elseif($ageIsAtLeast26months && ($ddExists || $dfExists
            || $hkExists || $hhExists //adding $hkExists && $hhExists in case of incomplete exterior data
            )) {
            $kindsForOutput[] = ExteriorKind::HH_;
        }

        if(!$animal->getIsAlive()) { $kindsForOutput[] = ExteriorKind::DO_; }

        if($ddExists || $dfExists || $vgExists
            || $hkExists || $hhExists //adding $hkExists && $hhExists in case of incomplete exterior data
        ) { $kindsForOutput[] = ExteriorKind::HK_; }

        
        if($currentKind != null && !in_array($currentKind, $kindsForOutput)) {
            $kindsForOutput[] = $currentKind;
        }

        sort($kindsForOutput);
        foreach ($kindsForOutput as $kind) {
            $output[] = ['code' => $kind];
        }

        return $output;
    }


}