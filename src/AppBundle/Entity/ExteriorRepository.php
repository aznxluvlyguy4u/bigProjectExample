<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Util\NullChecker;
use AppBundle\Util\NumberUtil;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

/**
 * Class ExteriorRepository
 * @package AppBundle\Entity
 */
class ExteriorRepository extends MeasurementRepository {

    /**
     * If no Exterior is found a blank Exterior entity is returned
     * 
     * @param Animal $animal
     * @return Exterior
     */
    public function getLatestExterior(Animal $animal)
    {
        //Measurement Criteria
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('animal', $animal))
            ->orderBy(['measurementDate' => Criteria::DESC])
            ->setMaxResults(1);
        
        $latestExterior = $this->getManager()->getRepository(Exterior::class)
            ->matching($criteria);

        if(sizeof($latestExterior) > 0) {
            $latestExterior = $latestExterior->get(0);
        } else { //create an empty default Exterior with default 0.0 values
            $latestExterior = new Exterior();
        }
        return $latestExterior;
    }


    /**
     * @param int $startYear
     * @param int $endYear
     * @return Collection
     */
    public function getExteriorsBetweenYears($startYear, $endYear)
    {
        $startDate = $startYear.'-01-01 00:00:00';
        $startTime = new \DateTime($startDate);

        $endYear = $endYear.'-12-31 23:59:59';
        $endTime = new \DateTime($endYear);

        $criteria = Criteria::create()
            ->where(Criteria::expr()->gte('measurementDate', $startTime)) //greater or equal to this startTime
            ->andWhere(Criteria::expr()->lte('measurementDate', $endTime)) //less or equal to this endTime
            ->orderBy(['measurementDate' => Criteria::ASC])
        ;

        $measurements = $this->getManager()->getRepository(Exterior::class)
            ->matching($criteria);

        return $measurements;
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
              SELECT MIN(measurement.id) as min_id
              FROM measurement INNER JOIN exterior x ON measurement.id = x.id 
              GROUP BY measurement_date, type, x.animal_id, x.kind, x.skull, x.muscularity, x.progress, x.proportion, x.exterior_type, x.leg_work, x.fur, x.general_appearence, x.height, x.breast_depth, x.torso_length, x.markings 
              HAVING COUNT(*) > 1";
            $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

            foreach ($results as $result) {
                $minId = $result['min_id'];
                $sql = "DELETE FROM exterior WHERE id = '".$minId."'";
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
        $fixedContradictingExteriorsCount = $this->fixContradictingMeasurements();
        $fixedMissingValuesExteriorsCount = $this->fixDuplicateMeasurementsMissingHeightProgressAndKind();

        $totalCount = $fixedContradictingExteriorsCount + $fixedMissingValuesExteriorsCount;

        if($totalCount > 0) {
            $message =
                'CONTRADICTING EXTERIORS ARE NOT CHECKED!'
//                'Fixed contradicting exteriors: ' . $fixedContradictingExteriorsCount TODO IF NECESSARY
                .'| Fixed duplicate exteriors with missing height, kind, progress: ' . $fixedMissingValuesExteriorsCount;
        } else {
            $message = 'No exterior fixes implemented';
        }

        return [Constant::COUNT => $totalCount, Constant::MESSAGE_NAMESPACE => $message];
    }


    /**
     * @return int
     */
    private function fixContradictingMeasurements()
    {
        //TODO if necessary
        $em = $this->getManager();
        return 0;
    }


    /**
     * @return int
     */
    private function fixDuplicateMeasurementsMissingHeightProgressAndKind()
    {
        $isGetGroupedByAnimalAndDate = true;
        $exteriorsByAnimalIdAndDate = $this->getDuplicateMeasurementsMissingHeightProgressAndKind($isGetGroupedByAnimalAndDate);

        $measurementsFixedCount = 0;
        foreach ($exteriorsByAnimalIdAndDate as $exteriorGroup) {

            $exteriorGroupSize = count($exteriorGroup);

            for($i = 0; $i < $exteriorGroupSize; $i++) {
                for($j = $i+1; $j < $exteriorGroupSize; $j++) {

                    if($i != $j) { //Just an extra check to be sure

                        $hasDeletedAnExterior = $this->deleteExteriorDuplicateMissingHeightProgressAndKind($exteriorGroup[$i], $exteriorGroup[$j]);
                        if($hasDeletedAnExterior) {
                            $measurementsFixedCount++;
                        }
                    }
                }
            }
        }

        return $measurementsFixedCount;
    }


    /**
     * @param array $exterior1
     * @param array $exterior2
     * @return bool
     */
    private function deleteExteriorDuplicateMissingHeightProgressAndKind($exterior1, $exterior2)
    {
        $em = $this->getManager();
        $hasDeletedAnExterior = false;

        $exterior1Id = $exterior1['measurement_id'];
        $exterior2Id = $exterior2['measurement_id'];
        $hasExterior1EmptyHeightKindAndProgress = $this->hasEmptyHeightKindAndProgress($exterior1);
        $hasExterior2EmptyHeightKindAndProgress = $this->hasEmptyHeightKindAndProgress($exterior2);

        if($hasExterior1EmptyHeightKindAndProgress && !$hasExterior2EmptyHeightKindAndProgress){
            if($this->isInspectorNotMissing($exterior2, $exterior1) &&
            $this->areNonHeightKindProgressAndInspectorExteriorValuesIdentical($exterior1, $exterior2)) {
                $sql = "DELETE FROM exterior WHERE id = ".$exterior1Id;
                $em->getConnection()->exec($sql);
                $sql = "DELETE FROM measurement WHERE id = ".$exterior1Id;
                $em->getConnection()->exec($sql);
                $hasDeletedAnExterior = true;
                $this->printDeletedRows($exterior1); //FIXME
            }

        } elseif (!$hasExterior1EmptyHeightKindAndProgress && $hasExterior2EmptyHeightKindAndProgress) {
            if($this->isInspectorNotMissing($exterior1, $exterior2) &&
                $this->areNonHeightKindProgressAndInspectorExteriorValuesIdentical($exterior1, $exterior2)) {
                $sql = "DELETE FROM exterior WHERE id = ".$exterior2Id;
                $em->getConnection()->exec($sql);
                $sql = "DELETE FROM measurement WHERE id = ".$exterior2Id;
                $em->getConnection()->exec($sql);
                $hasDeletedAnExterior = true;
                $this->printDeletedRows($exterior2); //FIXME
            }
        }
        return $hasDeletedAnExterior;
    }


    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getDuplicateMeasurementsMissingHeightProgressAndKind($isGetGroupedByAnimalAndDate = false)
    {
        $sql = "SELECT n.id as measurement_id, a.id as animal_id, n.animal_id_and_date, inspector_id,
                      DATE(n.measurement_date) as measurement_date, CONCAT(a.uln_country_code, a.uln_number) as uln, 
                      CONCAT(a.pedigree_country_code, a.pedigree_number) as stn, 
                      DATE(a.date_of_birth) as date_of_birth, z.* FROM measurement n
                  INNER JOIN (
                               SELECT m.animal_id_and_date
                               FROM measurement m
                                 INNER JOIN exterior x ON m.id = x.id
                               GROUP BY m.animal_id_and_date, type, x.animal_id, x.skull, x.muscularity, x.proportion, x.exterior_type, x.leg_work, x.fur, x.general_appearence, x.breast_depth, x.torso_length, x.markings
                               HAVING (COUNT(*) > 1)
                             ) t on t.animal_id_and_date = n.animal_id_and_date
                  INNER JOIN exterior z ON z.id = n.id
                  LEFT JOIN person i ON i.id = n.inspector_id
                  LEFT JOIN animal a ON a.id = z.animal_id";
        $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

        if($isGetGroupedByAnimalAndDate) {
            return $this->groupSqlMeasurementResultsByAnimalIdAndDate($results);
        } else {
            return $results;
        }
    }


    public function getContradictingExteriors()
    {

    }


    private function printDeletedRows($input)
    {
        $row = $input['animal_id_and_date'].$input['kind'].$input['progress'].$input['height'].$input['inspector_id'];
        file_put_contents('/home/data/JVT/projects/NSFO/FEATURES/MixBlup/dump/'.'exterior_errors_MAAAN.txt', $row."\n", FILE_APPEND);
    }


    /**
     * @param array $exteriorArray
     * @return bool
     */
    private function hasEmptyHeightKindAndProgress($exteriorArray)
    {
        $kind = $exteriorArray[JsonInputConstant::KIND];
        $height = floatval($exteriorArray[JsonInputConstant::HEIGHT]);
        $progress = floatval($exteriorArray[JsonInputConstant::PROGRESS]);

        $isHeightZero = !NullChecker::floatIsNotZero($height);
        $isProgressZero = !NullChecker::floatIsNotZero($progress);
        $isKindNull = !NullChecker::isNotNull($kind);

        return $isHeightZero && $isProgressZero && $isKindNull;
    }


    /**
     * @param array $exteriorArrayToKeep
     * @param array $exteriorArrayToDelete
     * @return bool
     */
    private function isInspectorNotMissing($exteriorArrayToKeep, $exteriorArrayToDelete)
    {
        if($exteriorArrayToDelete['inspector_id'] == null) {
            return true;

        } else {
            if($exteriorArrayToKeep['inspector_id'] != null) {
                return true;
            } else {
                return false;
            }
        }
    }


    /**
     * @param array $exterior1
     * @param array $exterior2
     * @return bool
     */
    private function areNonHeightKindProgressAndInspectorExteriorValuesIdentical($exterior1, $exterior2) {

        return
            $exterior1['animal_id'] == $exterior2['animal_id'] &&
            $exterior1['animal_id_and_date'] == $exterior2['animal_id_and_date'] &&
            $exterior1['measurement_date'] == $exterior2['measurement_date'] &&
            $exterior1['skull'] == $exterior2['skull'] &&
            $exterior1['muscularity'] == $exterior2['muscularity'] &&
            $exterior1['proportion'] == $exterior2['proportion'] &&
            $exterior1['exterior_type'] == $exterior2['exterior_type'] &&
            $exterior1['leg_work'] == $exterior2['leg_work'] &&
            $exterior1['fur'] == $exterior2['fur'] &&
            $exterior1['general_appearance'] == $exterior2['general_appearance'] &&
            $exterior1['breast_depth'] == $exterior2['breast_depth'] &&
            $exterior1['torso_length'] == $exterior2['torso_length'] &&
            $exterior1['markings'] == $exterior2['markings'];
    }
}