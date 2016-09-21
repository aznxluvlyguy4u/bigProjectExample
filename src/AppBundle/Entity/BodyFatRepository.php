<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\MeasurementConstant;
use AppBundle\Util\NumberUtil;
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
              SELECT MIN(m.id) as min_id, COUNT(*), measurement_date, animal_id, m.inspector_id, fat1.fat as fat1, fat2.fat as fat2, fat3.fat as fat3
              FROM body_fat b INNER JOIN measurement m ON m.id = b.id
              LEFT JOIN fat1 ON b.fat1_id = fat1.id
              LEFT JOIN fat2 ON b.fat2_id = fat2.id
              LEFT JOIN fat3 ON b.fat3_id = fat3.id
              GROUP BY measurement_date, type, b.animal_id, m.inspector_id, fat1.fat, fat2.fat, fat3.fat
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
}