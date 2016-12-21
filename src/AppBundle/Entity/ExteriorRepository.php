<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Util\NullChecker;
use AppBundle\Util\NumberUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

/**
 * Class ExteriorRepository
 * @package AppBundle\Entity
 */
class ExteriorRepository extends MeasurementRepository {

    const FILE_NAME = 'exterior_deleted_duplicates';
    const FILE_EXTENSION = '.txt';
    const FILE_NAME_TIME_STAMP_FORMAT = 'Y-m-d_H';

    /** @var boolean */
    private $isPrintDeletedExteriors;

    /** @var string */
    private $mutationsFolder;


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

        $sql = "SELECT m.id as id, measurement_date, x.*, p.person_id, p.first_name, p.last_name
                FROM measurement m
                  INNER JOIN exterior x ON x.id = m.id
                  LEFT JOIN person p ON p.id = m.inspector_id
                  INNER JOIN animal a ON a.id = x.animal_id
                WHERE x.animal_id = ".$animal->getId()." ORDER BY measurement_date DESC";
        $retrievedMeasurementData = $this->getManager()->getConnection()->query($sql)->fetchAll();

        foreach ($retrievedMeasurementData as $measurementData)
        {
            $results[] = [
                JsonInputConstant::MEASUREMENT_DATE => TimeUtil::getDateTimeFromNullCheckedArrayValue('measurement_date', $measurementData, $nullFiller),
                JsonInputConstant::HEIGHT => Utils::fillNullOrEmptyString($measurementData['height'], $nullFiller),
                JsonInputConstant::KIND => Utils::fillNullOrEmptyString($measurementData['kind'], $nullFiller),
                JsonInputConstant::PROGRESS => Utils::fillNullOrEmptyString($measurementData['progress'], $nullFiller),
                JsonInputConstant::SKULL => Utils::fillNullOrEmptyString($measurementData['skull'], $nullFiller),
                JsonInputConstant::MUSCULARITY => Utils::fillNullOrEmptyString($measurementData['muscularity'], $nullFiller),
                JsonInputConstant::PROPORTION => Utils::fillNullOrEmptyString($measurementData['proportion'], $nullFiller),
                JsonInputConstant::TYPE => Utils::fillNullOrEmptyString($measurementData['exterior_type'], $nullFiller),
                JsonInputConstant::LEG_WORK => Utils::fillNullOrEmptyString($measurementData['leg_work'], $nullFiller),
                JsonInputConstant::FUR => Utils::fillNullOrEmptyString($measurementData['fur'], $nullFiller),
                JsonInputConstant::GENERAL_APPEARANCE => Utils::fillNullOrEmptyString($measurementData['general_appearence'], $nullFiller),
                JsonInputConstant::BREAST_DEPTH => Utils::fillNullOrEmptyString($measurementData['breast_depth'], $nullFiller),
                JsonInputConstant::TORSO_LENGTH => Utils::fillNullOrEmptyString($measurementData['torso_length'], $nullFiller),
                JsonInputConstant::MARKINGS => Utils::fillNullOrEmptyString($measurementData['markings'], $nullFiller),
                JsonInputConstant::INSPECTOR_ID => Utils::fillNullOrEmptyString($measurementData['person_id'], $nullFiller),
                JsonInputConstant::INSPECTOR_FIRST_NAME => Utils::fillNullOrEmptyString($measurementData['first_name'], $nullFiller),
                JsonInputConstant::INSPECTOR_LAST_NAME => Utils::fillNullOrEmptyString($measurementData['last_name'], $nullFiller),
            ];
        }
        return $results;
    }


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
     * NOTE! general_appearence is returned spelling corrected as general_appearance
     *
     * @param int $animalId
     * @param string $replacementString
     * @return array
     */
    public function getLatestExteriorBySql($animalId = null, $replacementString = null)
    {
        $nullResult = [
          JsonInputConstant::ID => $replacementString,
          JsonInputConstant::ANIMAL_ID => $replacementString,
          JsonInputConstant::SKULL => $replacementString,
          JsonInputConstant::MUSCULARITY => $replacementString,
          JsonInputConstant::PROPORTION => $replacementString,
          JsonInputConstant::EXTERIOR_TYPE => $replacementString,
          JsonInputConstant::LEG_WORK => $replacementString,
          JsonInputConstant::FUR => $replacementString,
          JsonInputConstant::GENERAL_APPEARANCE => $replacementString,
          JsonInputConstant::HEIGHT => $replacementString,
          JsonInputConstant::BREAST_DEPTH => $replacementString,
          JsonInputConstant::TORSO_LENGTH => $replacementString,
          JsonInputConstant::MARKINGS => $replacementString,
          JsonInputConstant::KIND => $replacementString,
          JsonInputConstant::PROGRESS => $replacementString,
          JsonInputConstant::MEASUREMENT_DATE => $replacementString,
        ];

        if(!is_int($animalId)) { return $nullResult; }

        $sqlBase = "SELECT x.id, x.animal_id, x.skull, x.muscularity, x.proportion, x.exterior_type, x.leg_work,
                      x.fur, x.general_appearence as general_appearance, x.height, x.breast_depth, x.torso_length, x.markings, x.kind, x.progress, m.measurement_date
                    FROM exterior x
                      INNER JOIN measurement m ON x.id = m.id
                      INNER JOIN (
                                   SELECT animal_id, max(m.measurement_date) as measurement_date
                                   FROM exterior e
                                     INNER JOIN measurement m ON m.id = e.id
                                   GROUP BY animal_id) y on y.animal_id = x.animal_id WHERE m.measurement_date = y.measurement_date ";

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
     * @param string $mutationsFolder
     * @return array
     */
    public function fixMeasurements($mutationsFolder = null)
    {
        $this->mutationsFolder = $mutationsFolder;
        if(NullChecker::isNotNull($this->mutationsFolder)) {
            $this->isPrintDeletedExteriors = true;
        } else {
            $this->isPrintDeletedExteriors = false;
        }

        $fixedMissingValuesExteriorsCount = $this->fixDuplicateMeasurementsMissingHeightProgressAndKind();
        $fixedContradictingExteriorsCount = $this->fixContradictingMeasurements();

        $totalCount = $fixedMissingValuesExteriorsCount + $fixedContradictingExteriorsCount;

        if($totalCount > 0) {
            $message =
                'Fixed contradicting exteriors: ' . $fixedContradictingExteriorsCount
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
        $exteriorsByAnimalIdAndDate = $this->getContradictingExteriorsGroupedByAnimalIdAndDate();

        $measurementsFixedCount = 0;
        foreach ($exteriorsByAnimalIdAndDate as $exteriorGroup) {

            $exteriorGroupSize = count($exteriorGroup);

            for($i = 0; $i < $exteriorGroupSize; $i++) {
                for($j = $i+1; $j < $exteriorGroupSize; $j++) {

                    if($i != $j) { //Just an extra check to be sure

                        $hasDeletedAnExterior = $this->deleteExteriorDuplicateShiftedTorsoValue($exteriorGroup[$i], $exteriorGroup[$j]);
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


        //After deleting the exteriors where one version is broken, merge the Exteriors where both are missing something the other has
        $exteriorsByAnimalIdAndDate = $this->getContradictingExteriorsGroupedByAnimalIdAndDate();

        $measurementsFixedCount = 0;
        foreach ($exteriorsByAnimalIdAndDate as $exteriorGroup) {

            $exteriorGroupSize = count($exteriorGroup);

            for($i = 0; $i < $exteriorGroupSize; $i++) {
                for($j = $i+1; $j < $exteriorGroupSize; $j++) {

                    if($i != $j) { //Just an extra check to be sure

                        $hasMergedAnExterior = $this->mergeExteriorDuplicateMissingHeightProgressAndKindOrInspector($exteriorGroup[$i], $exteriorGroup[$j]);
                        if($hasMergedAnExterior) {
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
     * @param array $exterior1
     * @param array $exterior2
     * @return bool
     */
    private function mergeExteriorDuplicateMissingHeightProgressAndKindOrInspector($exterior1, $exterior2)
    {
        $em = $this->getManager();
        $hasDeletedAnExterior = false;

        $exterior1Id = $exterior1['measurement_id'];
        $exterior2Id = $exterior2['measurement_id'];
        $hasExterior1EmptyHeightKindAndProgress = $this->hasEmptyHeightKindAndProgress($exterior1);
        $hasExterior2EmptyHeightKindAndProgress = $this->hasEmptyHeightKindAndProgress($exterior2);
        $inspectorId = $this->getNonContradictingInspectorIdFromAnyExterior($exterior1, $exterior2);

        if($hasExterior1EmptyHeightKindAndProgress && !$hasExterior2EmptyHeightKindAndProgress){
            if($inspectorId != null &&
                $this->areNonHeightKindProgressAndInspectorExteriorValuesIdentical($exterior1, $exterior2)) {
                $sql = "UPDATE measurement SET inspector_id = ".$inspectorId." WHERE id = ".$exterior2Id;
                $em->getConnection()->exec($sql);
                $sql = "DELETE FROM exterior WHERE id = ".$exterior1Id;
                $em->getConnection()->exec($sql);
                $sql = "DELETE FROM measurement WHERE id = ".$exterior1Id;
                $em->getConnection()->exec($sql);
                $hasDeletedAnExterior = true;
                $this->printDeletedRows($exterior1); //FIXME
            }

        } elseif (!$hasExterior1EmptyHeightKindAndProgress && $hasExterior2EmptyHeightKindAndProgress) {
            if($inspectorId != null &&
                $this->areNonHeightKindProgressAndInspectorExteriorValuesIdentical($exterior1, $exterior2)) {
                $sql = "UPDATE measurement SET inspector_id = ".$inspectorId." WHERE id = ".$exterior1Id;
                $em->getConnection()->exec($sql);
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
     * @param array $exterior1
     * @param array $exterior2
     * @return bool
     */ 
    private function deleteExteriorDuplicateShiftedTorsoValue($exterior1, $exterior2)
    {
        $em = $this->getManager();
        $hasDeletedAnExterior = false;

        $exterior1Id = $exterior1['measurement_id'];
        $exterior2Id = $exterior2['measurement_id'];
        $isExterior1ToBeDeleted = $this->areTorsoShiftedPair($exterior2, $exterior1);
        $isExterior2ToBeDeleted = false;
        if(!$isExterior1ToBeDeleted) {
            $isExterior2ToBeDeleted = $this->areTorsoShiftedPair($exterior1, $exterior2);
        }


        if($isExterior1ToBeDeleted){
            $sql = "DELETE FROM exterior WHERE id = ".$exterior1Id;
            $em->getConnection()->exec($sql);
            $sql = "DELETE FROM measurement WHERE id = ".$exterior1Id;
            $em->getConnection()->exec($sql);
            $hasDeletedAnExterior = true;
            $this->printDeletedRows($exterior1);

        } elseif ($isExterior2ToBeDeleted) {
            $sql = "DELETE FROM exterior WHERE id = ".$exterior2Id;
            $em->getConnection()->exec($sql);
            $sql = "DELETE FROM measurement WHERE id = ".$exterior2Id;
            $em->getConnection()->exec($sql);
            $hasDeletedAnExterior = true;
            $this->printDeletedRows($exterior2);
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


    /**
     * @return array
     */
    public function getContradictingExteriorsForExportFile()
    {
        return $this->getContradictingExteriors(true, false);
    }

    public function getContradictingExteriorsGroupedByAnimalIdAndDate()
    {
        return $this->getContradictingExteriors(false, true);
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getContradictingExteriors($isForExportFile = false, $isGetGroupedByAnimalAndDate = false)
    {
        if($isForExportFile) {
            $selectOutput = "CONCAT(a.uln_country_code, a.uln_number) as uln, CONCAT(a.pedigree_country_code, a.pedigree_number) as stn, a.name as AIIND, DATE(n.measurement_date) as meetdatum, DATE(a.date_of_birth) as geboortedatum,
       z.kind as EXT_KIND, z.progress as ONT, z.skull as KOP, muscularity as SPIER, proportion as EVE, exterior_type as TYPE, leg_work as BEEN, fur as VACHT, general_appearence as ALG, height as HOOGTE, breast_depth as BORST_DIEPTE, torso_length as TORSO_LENGTE, markings as KENMERKEN, i.last_name as inspector";
        } else {
            $selectOutput = "n.id as measurement_id, a.id as animal_id, n.animal_id_and_date, inspector_id,
                      DATE(n.measurement_date) as measurement_date, CONCAT(a.uln_country_code, a.uln_number) as uln, 
                      CONCAT(a.pedigree_country_code, a.pedigree_number) as stn, 
                      DATE(a.date_of_birth) as date_of_birth, z.*";
        }
        
        $sql = "SELECT ".$selectOutput." FROM measurement n
                  INNER JOIN (
                               SELECT m.animal_id_and_date
                               FROM measurement m
                                 INNER JOIN exterior x ON m.id = x.id
                               GROUP BY m.animal_id_and_date
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


    private function printDeletedRows($input)
    {
        if($this->isPrintDeletedExteriors) {
            $row = $input['animal_id_and_date'].$input['kind'].$input['progress'].$input['height'].$input['inspector_id'];
            $filePath = $this->mutationsFolder.'/'.self::FILE_NAME.TimeUtil::getTimeStampNow(self::FILE_NAME_TIME_STAMP_FORMAT).self::FILE_EXTENSION;
            file_put_contents($filePath, $row."\n", FILE_APPEND);
        }
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
    private function getNonContradictingInspectorIdFromAnyExterior($exterior1, $exterior2)
    {
        $inspectorId1 = $exterior1['inspector_id'];
        $inspectorId2 = $exterior2['inspector_id'];

        if($inspectorId1 == $inspectorId2) {
            return $inspectorId1;
        } elseif ($inspectorId1 == null && NullChecker::isNotNull($inspectorId2)) {
            return $inspectorId2;
        } elseif (NullChecker::isNotNull($inspectorId1) && $inspectorId2 == null) {
            return $inspectorId1;
        } else {
            return null;
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
            $exterior1['general_appearence'] == $exterior2['general_appearence'] &&
            $exterior1['breast_depth'] == $exterior2['breast_depth'] &&
            $exterior1['torso_length'] == $exterior2['torso_length'] &&
            $exterior1['markings'] == $exterior2['markings'];
    }


    /**
     * @param array $exteriorArrayToDelete
     * @param array $exteriorToKeep
     * @return bool
     */
    private function areTorsoShiftedPair($exteriorToKeep, $exteriorArrayToDelete) {

        $isDifferencesVerified = 
            NullChecker::numberIsNull($exteriorArrayToDelete['progress']) &&
            NullChecker::numberIsNull($exteriorArrayToDelete['height']) &&
            NullChecker::numberIsNull($exteriorArrayToDelete['torso_length'])
            && $exteriorArrayToDelete['breast_depth'] == $exteriorToKeep['torso_length']
            && $exteriorArrayToDelete['inspector_id'] == null && $exteriorToKeep['inspector_id'] != null;

        $isSimilaritiesVerified =
            $exteriorArrayToDelete['animal_id'] == $exteriorToKeep['animal_id'] &&
            $exteriorArrayToDelete['animal_id_and_date'] == $exteriorToKeep['animal_id_and_date'] &&
            $exteriorArrayToDelete['measurement_date'] == $exteriorToKeep['measurement_date'] &&
            $exteriorArrayToDelete['skull'] == $exteriorToKeep['skull'] &&
            $exteriorArrayToDelete['muscularity'] == $exteriorToKeep['muscularity'] &&
            $exteriorArrayToDelete['proportion'] == $exteriorToKeep['proportion'] &&
            $exteriorArrayToDelete['exterior_type'] == $exteriorToKeep['exterior_type'] &&
            $exteriorArrayToDelete['leg_work'] == $exteriorToKeep['leg_work'] &&
            $exteriorArrayToDelete['fur'] == $exteriorToKeep['fur'] &&
            $exteriorArrayToDelete['general_appearence'] == $exteriorToKeep['general_appearence'];


        if(NullChecker::isNotNull($exteriorToKeep['kind'])) {
            $isNotMissingExtKind = true;
        } else {
            if(NullChecker::isNull($exteriorArrayToDelete['kind'])) {
                $isNotMissingExtKind = true;
            } else {
                $isNotMissingExtKind = false;
            }
        }

        return $isDifferencesVerified && $isSimilaritiesVerified && $isNotMissingExtKind;
    }
}