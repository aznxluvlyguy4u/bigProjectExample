<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\model\measurements\WeightData;
use AppBundle\Util\NumberUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\DBALException;

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

        $animalId = $animal->getId();

        $sql = "SELECT m.id as id, measurement_date, t.*, p.person_id, p.first_name, p.last_name
                FROM measurement m
                  INNER JOIN weight t ON t.id = m.id
                  LEFT JOIN person p ON p.id = m.inspector_id
                  INNER JOIN animal a ON a.id = t.animal_id
                WHERE m.is_active = TRUE AND t.is_revoked = FALSE AND t.animal_id = $animalId ORDER BY m.measurement_date";
        $retrievedMeasurementData = $this->getManager()->getConnection()->query($sql)->fetchAll();

        foreach ($retrievedMeasurementData as $measurementData)
        {
            $results[] = [
                JsonInputConstant::MEASUREMENT_DATE => TimeUtil::getDateTimeFromNullCheckedArrayValue('measurement_date', $measurementData, $nullFiller),
                JsonInputConstant::WEIGHT => floatval($measurementData['weight']),
                JsonInputConstant::IS_BIRTH_WEIGHT => Utils::fillNullOrEmptyString($measurementData['is_birth_weight'], $nullFiller),
                JsonInputConstant::IS_REVOKED => Utils::fillNullOrEmptyString($measurementData['is_revoked'], $nullFiller),
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
            ->andWhere(Criteria::expr()->eq('isActive', true))
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
                                     WHERE w.is_revoked = false
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
     * @return Collection|Weight[]
     */
    public function findByAnimalAndDate(Animal $animal, \DateTime $dateTime)
    {
        $criteria = $this->findByAnimalAndDateCriteria($animal, $dateTime)
            ->andWhere(Criteria::expr()->eq('isRevoked', false))
        ;

        return $this->getManager()->getRepository(Weight::class)->matching($criteria);
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


    private function fixContradictingMeasurements()
    {
        $em = $this->getManager();

        $isGetGroupedByAnimalAndDate = true;
        $weightsGroupedByAnimalAndDate = $this->getContradictingWeightsOldVersion($isGetGroupedByAnimalAndDate);

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
     * @param bool $isGetGroupedByAnimalAndDate
     * @return array
     * @throws DBALException
     */
    public function getContradictingWeightsOldVersion($isGetGroupedByAnimalAndDate = false)
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
     * @throws DBALException
     */
    public function getContradictingWeights()
    {
        $sql = "SELECT n.id as id, a.id as animal_id, n.animal_id_and_date, n.measurement_date, n.log_date, n.inspector_id,
                       weight.weight, weight.is_birth_weight, n.is_active
                FROM measurement n
                         INNER JOIN (
                    SELECT m.animal_id_and_date
                    FROM measurement m
                             INNER JOIN weight ON m.id = weight.id
                    WHERE m.is_active
                    GROUP BY m.animal_id_and_date
                    HAVING (COUNT(*) > 1)
                ) t on t.animal_id_and_date = n.animal_id_and_date
                         INNER JOIN weight ON n.id = weight.id
                         LEFT JOIN animal a ON a.id = weight.animal_id
                WHERE n.is_active";
        $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

        $resultsAsDataObject = array_map(function ($weightsInArray) {
            return new WeightData($weightsInArray);
        }, $results);

        return $this->groupSqlMeasurementObjectResultsByAnimalIdAndDate($resultsAsDataObject);
    }
}
