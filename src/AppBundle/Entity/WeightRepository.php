<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Util\NumberUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

/**
 * Class WeightRepository
 * @package AppBundle\Entity
 */
class WeightRepository extends BaseRepository {

    /**
     * @param Animal $animal
     * @return float
     */
    public function getLatestWeight(Animal $animal, $isIncludingBirthWeight = true)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('animal', $animal))
            ->andWhere(Criteria::expr()->eq('isRevoked', false))
            ->orderBy(['measurementDate' => Criteria::DESC])
            ->setMaxResults(1);

        if(!$isIncludingBirthWeight) {
            $criteria = $criteria->andWhere(Criteria::expr()->eq('isBirthWeight', false));
        }

        $latestWeightResult = $this->getEntityManager()->getRepository(Weight::class)
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

        $latestBirthWeightResult = $this->getEntityManager()->getRepository(Weight::class)
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
        $weightMeasurements = $this->getEntityManager()->getRepository(Weight::class)
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
        $em = $this->getEntityManager();

        $count = 0;
        
        $hasDuplicates = true;
        while($hasDuplicates) {
            $sql = "
              SELECT MIN(measurement.id) as min_id, COUNT(*), measurement_date, animal_id, weight, is_birth_weight, is_revoked
              FROM measurement INNER JOIN weight x ON measurement.id = x.id
              GROUP BY measurement_date, type, x.animal_id, x.weight, x.is_birth_weight, x.is_revoked
              HAVING COUNT(*) > 1";
            $results = $this->getEntityManager()->getConnection()->query($sql)->fetchAll();

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


    //TODO
    private function fixContradictingMeasurements()
    {
        $em = $this->getEntityManager();

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
        $em = $this->getEntityManager();

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
        $em = $this->getEntityManager();

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
        $em = $this->getEntityManager();

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
        $em = $this->getEntityManager();
        
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
        $results = $this->getEntityManager()->getConnection()->query($sql)->fetchAll();

        if($isGetGroupedByAnimalAndDate) {

            $weightsGroupedByAnimalAndDate = array();
            foreach ($results as $result) {
                $animalIdAndData = $result['animal_id_and_date'];
                if(array_key_exists($animalIdAndData, $weightsGroupedByAnimalAndDate)) {
                    $items = $weightsGroupedByAnimalAndDate[$animalIdAndData];
                    $items->add($result);
                    $weightsGroupedByAnimalAndDate[$animalIdAndData] = $items;
                } else {
                    //First entry
                    $items = new ArrayCollection();
                    $items->add($result);
                    $weightsGroupedByAnimalAndDate[$animalIdAndData] = $items;
                }
            }

            return $weightsGroupedByAnimalAndDate;

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
        $em = $this->getEntityManager();

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