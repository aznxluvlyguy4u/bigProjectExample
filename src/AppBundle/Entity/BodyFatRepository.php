<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\MeasurementConstant;
use AppBundle\Enumerator\MeasurementType;
use AppBundle\Util\MeasurementsUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\NumberUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * Class BodyFatRepository
 * @package AppBundle\Entity
 */
class BodyFatRepository extends MeasurementRepository {


    /**
     * @param Animal $animal
     * @return array
     */
    public function getAllOfAnimalBySql(Animal $animal, $nullFiller = '')
    {
        $results = [];
        //null check
        if(!($animal instanceof Animal)) { return $results; }
        elseif(!is_int($animal->getId())){ return $results; }

        $sql = "SELECT m.id as id, measurement_date, fat1.fat as fat1 , fat2.fat as fat2 , fat3.fat as fat3,
                p.person_id, p.first_name, p.last_name
                FROM measurement m
                INNER JOIN body_fat bf ON bf.id = m.id
                  LEFT JOIN fat1 ON bf.fat1_id = fat1.id
                  LEFT JOIN fat2 ON bf.fat2_id = fat2.id
                  LEFT JOIN fat3 ON bf.fat3_id = fat3.id
                  LEFT JOIN person p ON p.id = m.inspector_id
                WHERE bf.animal_id = ".$animal->getId();
        $retrievedMeasurementData = $this->getManager()->getConnection()->query($sql)->fetchAll();
        
        foreach ($retrievedMeasurementData as $measurementData)
        {
            $results[] = [
                JsonInputConstant::MEASUREMENT_DATE => TimeUtil::getDateTimeFromNullCheckedArrayValue('measurement_date', $measurementData, $nullFiller),
                JsonInputConstant::FAT1 => Utils::fillNullOrEmptyString($measurementData['fat1'], $nullFiller),
                JsonInputConstant::FAT2 => Utils::fillNullOrEmptyString($measurementData['fat2'], $nullFiller),
                JsonInputConstant::FAT3 => Utils::fillNullOrEmptyString($measurementData['fat3'], $nullFiller),
                JsonInputConstant::PERSON_ID =>  Utils::fillNullOrEmptyString($measurementData['person_id'], $nullFiller),
                JsonInputConstant::FIRST_NAME => Utils::fillNullOrEmptyString($measurementData['first_name'], $nullFiller),
                JsonInputConstant::LAST_NAME => Utils::fillNullOrEmptyString($measurementData['last_name'], $nullFiller),
            ];
        }
        return $results;
    }


    /**
     * @param Animal $animal
     * @return array
     */
    public function getLatestBodyFat(Animal $animal)
    {
        $bodyFat = array();

        //Measurement Criteria
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('animal', $animal))
            ->orderBy(['measurementDate' => Criteria::DESC])
            ->setMaxResults(1);

        /**
         * @var BodyFat $latestBodyFat
         */
        $latestBodyFat = $this->getManager()->getRepository(BodyFat::class)
            ->matching($criteria);

        if(sizeof($latestBodyFat) > 0) {
            $latestBodyFat = $latestBodyFat->get(0);
            $measurementDate = $latestBodyFat->getMeasurementDate();
            $fatOne = $latestBodyFat->getFat1()->getFat();
            $fatTwo = $latestBodyFat->getFat2()->getFat();
            $fatThree = $latestBodyFat->getFat3()->getFat();

            $bodyFat[MeasurementConstant::DATE] = $measurementDate;
            $bodyFat[MeasurementConstant::ONE] = $fatOne;
            $bodyFat[MeasurementConstant::TWO] = $fatTwo;
            $bodyFat[MeasurementConstant::THREE] = $fatThree;
        } else {
            $bodyFat[MeasurementConstant::DATE] = '';
            $bodyFat[MeasurementConstant::ONE] = 0.00;
            $bodyFat[MeasurementConstant::TWO] = 0.00;
            $bodyFat[MeasurementConstant::THREE] = 0.00;
        }
        return $bodyFat;
    }


    /**
     * @param Animal $animal
     * @return string
     */
    public function getLatestBodyFatAsString(Animal $animal)
    {
        $bodyFats = $this->getLatestBodyFat($animal);

        $fat1 = $bodyFats[MeasurementConstant::ONE];
        $fat2 = $bodyFats[MeasurementConstant::TWO];
        $fat3 = $bodyFats[MeasurementConstant::THREE];
        
        if($fat1 == 0 && $fat2 == 0 && $fat3 == 0) {
            return '';
        } else {
            return $fat1.'/'.$fat2.'/'.$fat3;
        }
    }


    /**
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteDuplicates()
    {
        $count = 0;
        $hasDuplicates = true;
        while($hasDuplicates) {
            $sql = "
              SELECT MIN(m.id) as min_id, COUNT(*), m.measurement_date, b.animal_id, m.inspector_id, fat1.fat as fat1, fat2.fat as fat2, fat3.fat as fat3
              FROM body_fat b INNER JOIN measurement m ON m.id = b.id
              LEFT JOIN fat1 ON b.fat1_id = fat1.id
              LEFT JOIN fat2 ON b.fat2_id = fat2.id
              LEFT JOIN fat3 ON b.fat3_id = fat3.id
              GROUP BY m.measurement_date, m.type, b.animal_id, m.inspector_id, fat1.fat, fat2.fat, fat3.fat
              HAVING COUNT(*) > 1";
            $results = $this->getManager()->getConnection()->query($sql)->fetchAll();
            
            foreach ($results as $result) {
                $this->deleteBodyFatMeasurement($result['min_id']);
                $count++;
            }

            if(count($results) == 0) { $hasDuplicates = false; }
        }
        return $count;
    }


    /**
     * @return array
     */
    public function fixMeasurements()
    {
        $fixedContradictingWeightsCount = $this->fixContradictingMeasurements();
        $totalCount = $fixedContradictingWeightsCount;

        $message = 'Fixed contradicting BodyFats: ' . $fixedContradictingWeightsCount;

        return [Constant::COUNT => $totalCount, Constant::MESSAGE_NAMESPACE => $message];
    }


    /**
     * @return int
     */
    private function fixContradictingMeasurements()
    {
        $em = $this->getManager();
        $isGetGroupedByAnimalAndDate = true;
        $bodyFatsGroupedByAnimalAndDate = $this->getContradictingBodyFats($isGetGroupedByAnimalAndDate);


        $measurementsFixedCount = 0;
        foreach ($bodyFatsGroupedByAnimalAndDate as $bodyFatGroup) {

            if(count($bodyFatGroup) == 2) {

                $firstMeasurement = $bodyFatGroup[0];
                $secondMeasurement = $bodyFatGroup[1];
                $firstMeasurementId = $firstMeasurement[JsonInputConstant::ID];
                $secondMeasurementId = $secondMeasurement[JsonInputConstant::ID];
                $isFirstMeasurementAllOnes = $this->areAllFatValuesOne($firstMeasurement);
                $isSecondMeasurementAllOnes = $this->areAllFatValuesOne($secondMeasurement);
                $hasFirstMeasurementInspector = $firstMeasurement[JsonInputConstant::INSPECTOR_ID] != null;
                $hasSecondMeasurementInspector = $secondMeasurement[JsonInputConstant::INSPECTOR_ID] != null;

                if($isFirstMeasurementAllOnes && $isSecondMeasurementAllOnes) {
                    if($hasFirstMeasurementInspector) {
                        $this->deleteBodyFatMeasurement($secondMeasurementId);
                        $measurementsFixedCount++;
                    } else {
                        //Either this measurement has an Inspector or both don't have one
                        $this->deleteBodyFatMeasurement($firstMeasurementId);
                        $measurementsFixedCount++;
                    }
                } elseif(!$isFirstMeasurementAllOnes && $isSecondMeasurementAllOnes) {
                    if($hasFirstMeasurementInspector || !$hasSecondMeasurementInspector) {
                        $this->deleteBodyFatMeasurement($secondMeasurementId);
                        $measurementsFixedCount++;
                    }

                } elseif($isFirstMeasurementAllOnes && !$isSecondMeasurementAllOnes) {
                    if(!$hasFirstMeasurementInspector || $hasSecondMeasurementInspector) {
                        $this->deleteBodyFatMeasurement($secondMeasurementId);
                        $measurementsFixedCount++;
                    }
                }
            }
        }

        return $measurementsFixedCount;
    }


    /**
     * @param array $bodyFatMeasurement
     * @return bool
     */
    private function areAllFatValuesOne($bodyFatMeasurement)
    {
        $fat1 = floatval($bodyFatMeasurement[JsonInputConstant::FAT1]);
        $fat2 = floatval($bodyFatMeasurement[JsonInputConstant::FAT2]);
        $fat3 = floatval($bodyFatMeasurement[JsonInputConstant::FAT3]);
        $accuracy = 0.0001;

        return  NumberUtil::areFloatsEqual($fat1, 1.0, $accuracy) &&
                NumberUtil::areFloatsEqual($fat2, 1.0, $accuracy) &&
                NumberUtil::areFloatsEqual($fat3, 1.0, $accuracy);
    }


    /**
     * @param $bodyFatId
     * @throws \Doctrine\DBAL\DBALException
     */
    private function deleteBodyFatMeasurement($bodyFatId)
    {
        $em = $this->getManager();

        $sql = "SELECT fat1_id, fat2_id, fat3_id FROM body_fat WHERE id = '".$bodyFatId."'";
        $fatIds = $this->getManager()->getConnection()->query($sql)->fetch();
        $fat1Id = $fatIds['fat1_id'];
        $fat2Id = $fatIds['fat2_id'];
        $fat3Id = $fatIds['fat3_id'];

        $sql = "DELETE FROM body_fat WHERE id = '".$bodyFatId."'";
        $em->getConnection()->exec($sql);
        $sql = "DELETE FROM measurement WHERE id = '".$bodyFatId."'";
        $em->getConnection()->exec($sql);
        $sql = "DELETE FROM fat1 WHERE id = '".$fat1Id."'";
        $em->getConnection()->exec($sql);
        $sql = "DELETE FROM fat2 WHERE id = '".$fat2Id."'";
        $em->getConnection()->exec($sql);
        $sql = "DELETE FROM fat3 WHERE id = '".$fat3Id."'";
        $em->getConnection()->exec($sql);
    }


    /**
     * @param bool $isGetGroupedByAnimalAndDate
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getContradictingBodyFats($isGetGroupedByAnimalAndDate = false)
    {
        $sql = "SELECT n.id as id, a.id as animal_id, n.animal_id_and_date, n.measurement_date, 
                        fat1.fat as fat1,  fat2.fat as fat2, fat3.fat as fat3, n.inspector_id
                  FROM measurement n
                  INNER JOIN (
                               SELECT m.animal_id_and_date
                               FROM measurement m
                                 INNER JOIN body_fat x ON m.id = x.id
                               GROUP BY m.animal_id_and_date
                               HAVING (COUNT(*) > 1)
                             ) t on t.animal_id_and_date = n.animal_id_and_date
                  INNER JOIN body_fat z ON z.id = n.id
                  INNER JOIN fat1 ON z.fat1_id = fat1.id
                  INNER JOIN fat2 ON z.fat2_id = fat2.id
                  INNER JOIN fat3 ON z.fat3_id = fat3.id
                  LEFT JOIN animal a ON a.id = z.animal_id";
        $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

        if($isGetGroupedByAnimalAndDate) {
            return $this->groupSqlMeasurementResultsByAnimalIdAndDate($results);
        } else {
            return $results;
        }

    }


    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getContradictingBodyFatsForExportFile()
    {
        $em = $this->getManager();

        $sql = "
              SELECT n.id as metingId, a.id as dier_id, DATE(n.measurement_date) as meetdatum, DATE(a.date_of_birth) as geboortedatum,
                  z.weight as gewicht, is_birth_weight as is_geboortegewicht,
                  CONCAT(a.uln_country_code, a.uln_number) as uln, CONCAT(a.pedigree_country_code, a.pedigree_number) as stn, i.last_name as inspector
                FROM measurement n
                  INNER JOIN (
                               SELECT m.animal_id_and_date
                               FROM measurement m
                                 INNER JOIN (
                                              SELECT y.id FROM weight y  WHERE y.is_revoked = false
                                            ) x ON m.id = x.id
                               GROUP BY m.animal_id_and_date
                               HAVING (COUNT(*) > 1)
                             ) t on t.animal_id_and_date = n.animal_id_and_date
                  INNER JOIN weight z ON z.id = n.id
                  INNER JOIN animal a ON a.id = z.animal_id
                  LEFT JOIN person i ON i.id = n.inspector_id";
        return $em->getConnection()->query($sql)->fetchAll();
    }


    /**
     * @param bool $isGetGroupedByAnimalAndDate
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getAllBodyFatsBySql($isGetGroupedByAnimalAndDate = false)
    {
        $sql = "SELECT n.id as id, a.id as animal_id, n.animal_id_and_date, n.measurement_date, 
                        fat1.fat as fat1,  fat2.fat as fat2, fat3.fat as fat3, fat1_id, fat2_id, fat3_id,
                        n.inspector_id, p.last_name as inspector_last_name, CONCAT(a.uln_country_code, a.uln_number) as uln, CONCAT(a.pedigree_country_code, a.pedigree_number) as stn, a.name as vsm_id
                  FROM measurement n
                  INNER JOIN body_fat z ON z.id = n.id
                  INNER JOIN fat1 ON z.fat1_id = fat1.id
                  INNER JOIN fat2 ON z.fat2_id = fat2.id
                  INNER JOIN fat3 ON z.fat3_id = fat3.id
                  LEFT JOIN animal a ON a.id = z.animal_id
                  LEFT JOIN person p ON p.id = n.inspector_id";
        $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

        if($isGetGroupedByAnimalAndDate) {
            return $this->groupSqlMeasurementResultsByAnimalIdAndDate($results);
        } else {
            return $results;
        }

    }


    /**
     * @param string $animalIdAndDate
     * @param int $inspectorId
     * @param float $fat1Value
     * @param float $fat2Value
     * @param float $fat3Value
     * @return bool
     */
    public function insertNewBodyFat($animalIdAndDate, $fat1Value, $fat2Value, $fat3Value, $inspectorId = null)
    {
        if( !(NullChecker::floatIsNotZero($fat1Value) && NullChecker::floatIsNotZero($fat2Value) && NullChecker::floatIsNotZero($fat3Value)) ) {
            return false; //Insert is unsuccessful
        }
        
        $parts = MeasurementsUtil::getIdAndDateFromAnimalIdAndDateString($animalIdAndDate);
        $animalId = $parts[MeasurementConstant::ANIMAL_ID];
        $measurementDateString = $parts[MeasurementConstant::DATE];
        
        $isInsertParentSuccessful = $this->insertNewMeasurementInParentTable($animalIdAndDate, $measurementDateString, MeasurementType::BODY_FAT, $inspectorId);
        $bodyFatId = $this->getMaxId();
        if($isInsertParentSuccessful) {

            $fatValues = [ 1 => $fat1Value, 2 => $fat2Value, 3 => $fat3Value ];
            $fatIds = array();

            for($fatNumber = 1; $fatNumber <= 3; $fatNumber++) {
                $fatIds[$fatNumber] = $this->insertNewFat($animalIdAndDate, $fatValues[$fatNumber], $fatNumber, $inspectorId);
            }

            if(in_array(null, $fatIds)) {
                //Delete any orphaned fat1, fat2 & fat3 measurements
                $this->deleteOrphanedFats();
                //Delete incomplete bodyFat measurement in parent table
                $sql = "DELETE FROM measurement WHERE id = " . $bodyFatId;
                $this->getManager()->getConnection()->exec($sql);
                return false; //Insert is unsuccessful
            }

            $sql = "INSERT INTO body_fat (id, animal_id, fat1_id, fat2_id, fat3_id) VALUES (".$bodyFatId.",".$animalId.",".$fatIds[1].",".$fatIds[2].",".$fatIds[3].")";
            $this->getManager()->getConnection()->exec($sql);
            return true; //Insert is successful
        }
        return false; //Insert is unsuccessful
    }


    /**
     * @param string $animalIdAndDate
     * @param int $inspectorId
     * @param float $fatValue
     * @param int $fatTypeNumber
     * @return int
     */
    private function insertNewFat($animalIdAndDate, $fatValue, $fatTypeNumber, $inspectorId = null)
    {
        $measurementDateString = MeasurementsUtil::getIdAndDateFromAnimalIdAndDateString($animalIdAndDate)[MeasurementConstant::DATE];

        $isCorrectFatTypeNumber = $fatTypeNumber == 1 || $fatTypeNumber == 2 || $fatTypeNumber == 3;
        if( !(NullChecker::floatIsNotZero($fatValue) && $isCorrectFatTypeNumber) ){
            return null;
        }
        $tableName = 'fat'.$fatTypeNumber;

        switch ($fatTypeNumber) {
            case 1:
                $measurementType = MeasurementType::FAT1;
                break;
            case 2:
                $measurementType = MeasurementType::FAT2;
                break;
            case 3:
                $measurementType = MeasurementType::FAT3;
                break;
            default:
                $measurementType = '';
        }

        $measurementId = null;
        $isInsertParentSuccessful = $this->insertNewMeasurementInParentTable($animalIdAndDate, $measurementDateString, $measurementType, $inspectorId);
        if($isInsertParentSuccessful) {
            $sql = "INSERT INTO ".$tableName." (id, fat) VALUES (currval('measurement_id_seq'),'".$fatValue."')";
            $this->getManager()->getConnection()->exec($sql);

            $measurementId = $this->getMaxId();
        }
        return $measurementId;
    }


    /**
     * Delete fat1, fat2 and fat3 measurements without any BodyFat
     * @return int
     */
    public function deleteOrphanedFats()
    {
        //Fat1
        $sql = "SELECT fat1.id as id FROM fat1
                LEFT JOIN body_fat ON fat1.id = body_fat.fat1_id
                WHERE body_fat.id ISNULL";
        $resultsFat1 = $this->getManager()->getConnection()->query($sql)->fetchAll();
        foreach($resultsFat1 as $result) { $this->deleteOrphanedFat(MeasurementType::FAT1, $result['id']); }

        //Fat2
        $sql = "SELECT fat2.id as id FROM fat2
                LEFT JOIN body_fat ON fat2.id = body_fat.fat2_id
                WHERE body_fat.id ISNULL";
        $resultsFat2 = $this->getManager()->getConnection()->query($sql)->fetchAll();
        foreach($resultsFat2 as $result) { $this->deleteOrphanedFat(MeasurementType::FAT2, $result['id']); }

        //Fat3
        $sql = "SELECT fat3.id as id FROM fat3
                LEFT JOIN body_fat ON fat3.id = body_fat.fat3_id
                WHERE body_fat.id ISNULL";
        $resultsFat3 = $this->getManager()->getConnection()->query($sql)->fetchAll();
        foreach($resultsFat3 as $result) { $this->deleteOrphanedFat(MeasurementType::FAT3, $result['id']); }

        return count($resultsFat1 + $resultsFat2 + $resultsFat3);
    }


    /**
     * @param $fatType
     * @param $fatId
     */
    private function deleteOrphanedFat($fatType, $fatId)
    {
        $sql = "DELETE FROM '".$fatType."' WHERE id = " . $fatId;
        $this->getManager()->getConnection()->exec($sql);

        $sql = "DELETE FROM measurement WHERE id = " . $fatId;
        $this->getManager()->getConnection()->exec($sql);
    }
}