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
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

/**
 * Class WeightRepository
 * @package AppBundle\Entity
 */
class WeightRepository extends MeasurementRepository {


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

        $sql = "SELECT m.id as id, measurement_date, t.*, p.person_id, p.first_name, p.last_name
                FROM measurement m
                  INNER JOIN weight t ON t.id = m.id
                  LEFT JOIN person p ON p.id = m.inspector_id
                  INNER JOIN animal a ON a.id = t.animal_id
                WHERE t.animal_id = ".$animal->getId();
        $retrievedMeasurementData = $this->getManager()->getConnection()->query($sql)->fetchAll();

        foreach ($retrievedMeasurementData as $measurementData)
        {
            $results[] = [
                JsonInputConstant::MEASUREMENT_DATE => Utils::fillNullOrEmptyString($measurementData['measurement_date'], $nullFiller),
                JsonInputConstant::WEIGHT => Utils::fillNullOrEmptyString($measurementData['weight'], $nullFiller),
                JsonInputConstant::IS_BIRTH_WEIGHT => Utils::fillNullOrEmptyString($measurementData['is_birth_weight'], $nullFiller),
                JsonInputConstant::IS_REVOKED => Utils::fillNullOrEmptyString($measurementData['is_revoked'], $nullFiller),
                JsonInputConstant::PERSON_ID =>  Utils::fillNullOrEmptyString($measurementData['person_id'], $nullFiller),
                JsonInputConstant::FIRST_NAME => Utils::fillNullOrEmptyString($measurementData['first_name'], $nullFiller),
                JsonInputConstant::LAST_NAME => Utils::fillNullOrEmptyString($measurementData['last_name'], $nullFiller),
            ];
        }
        return $results;
    }
    
    
    /**
     * @param Animal $animal
     * @return float
     */
    public function getLatestWeight(Animal $animal, $isIncludingBirthWeight = true)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('animal', $animal))
            ->andWhere(Criteria::expr()->eq('isRevoked', false))
            ->orderBy(['measurementDate' => Criteria::DESC, 'logDate' => Criteria::DESC])
            ->setMaxResults(1);

        if(!$isIncludingBirthWeight) {
            $criteria = $criteria->andWhere(Criteria::expr()->eq('isBirthWeight', false));
        }

        $latestWeightResult = $this->getManager()->getRepository(Weight::class)
            ->matching($criteria);

        if(sizeof($latestWeightResult) > 0) {
            $latestWeightMeasurement = $latestWeightResult->get(0);
            $latestWeight = $latestWeightMeasurement->getWeight();
        } else {
            $latestWeight = 0.00;
        }

        return $latestWeight;
    }


    /**
     * @param Animal $animal
     * @return float
     */
    public function getLatestBirthWeight(Animal $animal)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('animal', $animal))
            ->andWhere(Criteria::expr()->eq('isBirthWeight', true))
            ->andWhere(Criteria::expr()->eq('isRevoked', false))
            ->orderBy(['measurementDate' => Criteria::DESC])
            ->setMaxResults(1);

        $latestBirthWeightResult = $this->getManager()->getRepository(Weight::class)
            ->matching($criteria);

        if(sizeof($latestBirthWeightResult) > 0) {
            $latestBirthWeightMeasurement = $latestBirthWeightResult->get(0);
            $latestBirthWeight = $latestBirthWeightMeasurement->getWeight();
        } else {
            $latestBirthWeight = 0.00;
        }
        return $latestBirthWeight;
    }


    /**
     * @param int $animalId
     * @param string $replacementString
     * @return array
     */
    public function getLatestWeightBySql($animalId = null, $replacementString = null)
    {
        $nullResult = [
            JsonInputConstant::ID => $replacementString,
            JsonInputConstant::ANIMAL_ID => $replacementString,
            JsonInputConstant::WEIGHT => $replacementString,
            JsonInputConstant::IS_BIRTH_WEIGHT => $replacementString,
            JsonInputConstant::MEASUREMENT_DATE => $replacementString,
        ];

        if(!is_int($animalId)) { return $nullResult; }

        $sqlBase = "SELECT x.id, x.animal_id, x.weight, x.is_birth_weight, m.measurement_date
                    FROM weight x
                      INNER JOIN measurement m ON x.id = m.id
                      INNER JOIN (
                                   SELECT animal_id, max(m.measurement_date) as measurement_date
                                   FROM weight w
                                     INNER JOIN measurement m ON m.id = w.id
                                   GROUP BY animal_id) y on y.animal_id = x.animal_id 
                      WHERE m.measurement_date = y.measurement_date AND x.is_revoked = false ";

        if(is_int($animalId)) {
            $filter = "AND x.animal_id = " . $animalId;
            $sql = $sqlBase.$filter;
            $result = $this->getManager()->getConnection()->query($sql)->fetch();
        } else {
            $filter = "";
            $sql = $sqlBase.$filter;
            $result = $this->getManager()->getConnection()->query($sql)->fetchAll();
        }
        return $result == false ? $nullResult : $result;
    }


    /**
     * @param Animal $animal
     * @param \DateTime $dateTime
     * @return Collection
     */
    public function findByAnimalAndDate(Animal $animal, \DateTime $dateTime)
    {
        $dayOfDateTime = TimeUtil::getDayOfDateTime($dateTime);
        $dayAfterDateTime = TimeUtil::getDayAfterDateTime($dateTime);

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('animal', $animal))
            ->andWhere(Criteria::expr()->gte('measurementDate', $dayOfDateTime))
            ->andWhere(Criteria::expr()->lt('measurementDate', $dayAfterDateTime))
            ->andWhere(Criteria::expr()->eq('isRevoked', false))
            ->orderBy(['measurementDate' => Criteria::DESC])
            ;

        /** @var Collection $weightMeasurements */
        $weightMeasurements = $this->getManager()->getRepository(Weight::class)
            ->matching($criteria);

        return $weightMeasurements;
    }


    /**
     * @param Animal $animal
     * @param \DateTime $dateTime
     * @return bool
     */
    public function isExistForAnimalOnDate(Animal $animal, \DateTime $dateTime)
    {
        $weightMeasurements = $this->findByAnimalAndDate($animal, $dateTime);
        if($weightMeasurements->count() > 0) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteDuplicates()
    {
        $em = $this->getManager();

        $count = 0;
        
        $hasDuplicates = true;
        while($hasDuplicates) {
            $sql = "
              SELECT MIN(measurement.id) as min_id, COUNT(*), measurement_date, animal_id, weight, is_birth_weight, is_revoked
              FROM measurement INNER JOIN weight x ON measurement.id = x.id
              GROUP BY measurement_date, type, x.animal_id, x.weight, x.is_birth_weight, x.is_revoked
              HAVING COUNT(*) > 1";
            $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

            foreach ($results as $result) {
                $minId = $result['min_id'];
                $sql = "DELETE FROM weight WHERE id = '".$minId."'";
                $em->getConnection()->exec($sql);
                $sql = "DELETE FROM measurement WHERE id = '".$minId."'";
                $em->getConnection()->exec($sql);
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
        $weightsFixedToBirthWeight = $this->fixBirthWeightsNotMarkedAsBirthWeight();
        $birthWeightsIncorrectlyMarkedAsBirthWeight = $this->fixWeightsIncorrectlyMarkedAsBirthWeight();
        $revokeBirthWeightsAbove10kg = $this->revokeBirthWeightsAbove10kg();
        $fixedContradictingWeightsCount = $this->fixContradictingMeasurements();

        $totalCount = $weightsFixedToBirthWeight + $birthWeightsIncorrectlyMarkedAsBirthWeight +
                        $revokeBirthWeightsAbove10kg + $fixedContradictingWeightsCount;

        if($totalCount > 0) {
            $message = 'Fixed Weights, set to BirthWeight: ' . $weightsFixedToBirthWeight
                .'|Fixed birthWeight to just Weight: ' . $birthWeightsIncorrectlyMarkedAsBirthWeight
                .'|Revoke birthWeights > 10kg: ' . $revokeBirthWeightsAbove10kg
                .'|Fixed contradicting weights: ' . $fixedContradictingWeightsCount;
        } else {
            $message = 'No weight fixes implemented';
        }

        return [Constant::COUNT => $totalCount, Constant::MESSAGE_NAMESPACE => $message];
    }


    private function fixContradictingMeasurements()
    {
        $em = $this->getManager();

        $isGetGroupedByAnimalAndDate = true;
        $weightsGroupedByAnimalAndDate = $this->getContradictingWeights($isGetGroupedByAnimalAndDate);

        $floatComparisonAccuracy = 0.001;
        $measurementsFixedCount = 0;
        foreach ($weightsGroupedByAnimalAndDate as $weightGroup)
        {
            $areAllBirthWeights = true;
            $weightValues = array();
            foreach($weightGroup as $weightMeasurement) {
                $weightValue = $weightMeasurement[JsonInputConstant::WEIGHT];
                $weightId = $weightMeasurement[JsonInputConstant::ID];
                if($weightMeasurement[JsonInputConstant::IS_BIRTH_WEIGHT] == false) { $areAllBirthWeights = false; }
                $weightValues[] = [JsonInputConstant::WEIGHT => $weightValue,
                                   JsonInputConstant::ID => $weightId];

                //1. Remove weights that are 0
                if(NumberUtil::areFloatsEqual(floatval($weightValue), 0.0, $floatComparisonAccuracy)) {
                    $sql = "UPDATE weight SET is_revoked = TRUE WHERE id = ".$weightId;
                    $em->getConnection()->exec($sql);
                    $measurementsFixedCount++;
                }

            }
            asort($weightValues);

            //2. If two measurements are identical accept in accuracy (number of decimals), choose the more accurate one
            if(count($weightValues) == 2) {
                $weight1 = floatval($weightValues[0][JsonInputConstant::WEIGHT]);
                $weight2 = floatval($weightValues[1][JsonInputConstant::WEIGHT]);
                $weightId1 = $weightValues[0][JsonInputConstant::ID];
                $weightId2 = $weightValues[1][JsonInputConstant::ID];
                $weight1HasDecimals = NumberUtil::hasDecimals($weight1);
                $weight2HasDecimals = NumberUtil::hasDecimals($weight2);
                
                if($weight1HasDecimals && !$weight2HasDecimals) {
                    //First round to nearest. If that doesn't work, round down
                    $roundedWeight1 = round($weight1);
                    if(!NumberUtil::areFloatsEqual($roundedWeight1, $weight2, $floatComparisonAccuracy)) { $roundedWeight1 = floor($weight1); }

                    if (NumberUtil::areFloatsEqual($roundedWeight1, $weight2)) {
                        //Keep the more accurate weight and revoke the other
                        $sql = "UPDATE weight SET is_revoked = TRUE WHERE id = ".$weightId2;
                        $em->getConnection()->exec($sql);
                        $measurementsFixedCount++;
                    }

                } elseif(!$weight1HasDecimals && $weight2HasDecimals) {
                    //First round to nearest. If that doesn't work, round down
                    $roundedWeight2 = round($weight2);
                    if(!NumberUtil::areFloatsEqual($weight1, $roundedWeight2, $floatComparisonAccuracy)) { $roundedWeight2 = floor($weight2); }

                    if (NumberUtil::areFloatsEqual($weight1, $roundedWeight2)) {
                        //Keep the more accurate weight and revoke the other
                        $sql = "UPDATE weight SET is_revoked = TRUE WHERE id = ".$weightId1;
                        $em->getConnection()->exec($sql);
                        $measurementsFixedCount++;
                    }
                }
            }

        }
        return $measurementsFixedCount;
    }


    /**
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function revokeBirthWeightsAbove10kg()
    {
        $em = $this->getManager();

        $sql = "SELECT COUNT(id) FROM weight w WHERE w.is_birth_weight = TRUE AND w.is_revoked = FALSE AND w.weight > 10.0";
        $count = $em->getConnection()->query($sql)->fetch()['count'];

        $sql = "UPDATE weight SET is_revoked = TRUE WHERE is_birth_weight = TRUE AND is_revoked = FALSE AND weight > 10.0";
        $em->getConnection()->exec($sql);

        return $count;
    }


    /**
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function fixBirthWeightsNotMarkedAsBirthWeight()
    {
        $em = $this->getManager();

        //First find the measurements
        $sql = "
              SELECT w.id FROM measurement m
                LEFT JOIN weight w ON m.id = w.id
                LEFT JOIN animal a ON a.id = w.animal_id
              WHERE DATE(m.measurement_date) = DATE(a.date_of_birth) AND w.is_birth_weight = false";
        $results = $em->getConnection()->query($sql)->fetchAll();
        
        foreach($results as $result)
        {
            $sql = "UPDATE weight SET is_birth_weight = true WHERE id = ".$result['id'];
            $em->getConnection()->exec($sql);
        }
        
        return count($results);
    }


    /**
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function fixWeightsIncorrectlyMarkedAsBirthWeight()
    {
        $em = $this->getManager();

        //First find the measurements
        $sql = "
              SELECT w.id FROM measurement m
                LEFT JOIN weight w ON m.id = w.id
                LEFT JOIN animal a ON a.id = w.animal_id
              WHERE DATE(m.measurement_date) <> DATE(a.date_of_birth) AND w.is_birth_weight = true";
        $results = $em->getConnection()->query($sql)->fetchAll();

        foreach($results as $result)
        {
            $sql = "UPDATE weight SET is_birth_weight = false WHERE id = ".$result['id'];
            $em->getConnection()->exec($sql);
        }

        return count($results);
    }


    /**
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getIncorrectBirthWeightBooleansInWeightsCount()
    {
        $em = $this->getManager();
        
        $sql = "
              SELECT w.id FROM measurement m
                LEFT JOIN weight w ON m.id = w.id
                LEFT JOIN animal a ON a.id = w.animal_id
              WHERE DATE(m.measurement_date) = DATE(a.date_of_birth) AND w.is_birth_weight = false";
        $results1 = $em->getConnection()->query($sql)->fetchAll();

        $sql = "
              SELECT w.id FROM measurement m
                LEFT JOIN weight w ON m.id = w.id
                LEFT JOIN animal a ON a.id = w.animal_id
              WHERE DATE(m.measurement_date) <> DATE(a.date_of_birth) AND w.is_birth_weight = true";
        $results2 = $em->getConnection()->query($sql)->fetchAll();
        
        return count($results1) + count($results2);
    }


    /**
     * @param bool $isGetGroupedByAnimalAndDate
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getContradictingWeights($isGetGroupedByAnimalAndDate = false)
    {
        $sql = "SELECT n.*, z.*, CONCAT(a.uln_country_code, a.uln_number) as uln, CONCAT(a.pedigree_country_code, a.pedigree_number) as stn, a.date_of_birth FROM measurement n
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
                  INNER JOIN animal a ON a.id = z.animal_id";
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
    public function getContradictingWeightsForExportFile()
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
     * @param bool $isIncludeRevokedWeights
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getAllWeightsBySql($isGetGroupedByAnimalAndDate = false, $isIncludeRevokedWeights = true)
    {
        if($isIncludeRevokedWeights) {
            $filter = '';
        } else {
            $filter = "WHERE is_revoked = false";
        }
        
        $sql = "SELECT n.*, z.*, CONCAT(a.uln_country_code, a.uln_number) as uln, CONCAT(a.pedigree_country_code, a.pedigree_number) as stn, a.name as vsm_id, a.date_of_birth, p.last_name as inspector_last_name FROM measurement n
                  INNER JOIN weight z ON z.id = n.id
                  INNER JOIN animal a ON a.id = z.animal_id
                  LEFT JOIN person p ON p.id = n.inspector_id ".$filter;
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
     * @param float $weightValue
     * @param bool $isBirthWeight
     * @param bool $isRevoked
     * @return bool
     */
    public function insertNewWeight($animalIdAndDate, $weightValue, $inspectorId = null, $isBirthWeight = false, $isRevoked = false)
    {
        $parts = MeasurementsUtil::getIdAndDateFromAnimalIdAndDateString($animalIdAndDate);
        $animalId = $parts[MeasurementConstant::ANIMAL_ID];
        $measurementDateString = $parts[MeasurementConstant::DATE];

        $isBirthWeight = StringUtil::getBooleanAsString($isBirthWeight);
        $isRevoked = StringUtil::getBooleanAsString($isRevoked);
        
        $isInsertSuccessful = false;
        $isInsertParentSuccessful = $this->insertNewMeasurementInParentTable($animalIdAndDate, $measurementDateString, MeasurementType::WEIGHT, $inspectorId);
        if($isInsertParentSuccessful && NullChecker::floatIsNotZero($weightValue)) {
            $sql = "INSERT INTO weight (id, animal_id, weight, is_birth_weight, is_revoked) VALUES (currval('measurement_id_seq'),'".$animalId."','".$weightValue."',".$isBirthWeight.",".$isRevoked.")";
            $this->getManager()->getConnection()->exec($sql);
            $isInsertSuccessful = true;
        }
        return $isInsertSuccessful;
    }
}