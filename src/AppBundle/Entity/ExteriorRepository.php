<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\Variable;
use AppBundle\Util\NullChecker;
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

    const DELETE_FROM_EXTERIOR_WHERE_ID = "DELETE FROM exterior WHERE id = ";

    /** @var boolean */
    private $isPrintDeletedExteriors;

    /** @var string */
    private $mutationsFolder;


    /**
     * @param Animal $animal
     * @param string $nullFiller
     * @param bool $ignoreDeleted
     * @return array
     */
    public function getAllOfAnimalBySql(Animal $animal, $nullFiller = '', $ignoreDeleted = true)
    {
        $results = [];
        //null check
        if(!($animal instanceof Animal || !is_int($animal->getId()))) { return $results; }

        $deletedFilterString = '';
        if($ignoreDeleted) { $deletedFilterString = ' AND m.is_active = TRUE '; }

        $sql = "SELECT m.id as id, measurement_date, x.*, p.person_id, p.first_name, p.last_name
                FROM measurement m
                  INNER JOIN exterior x ON x.id = m.id
                  LEFT JOIN person p ON p.id = m.inspector_id
                  INNER JOIN animal a ON a.id = x.animal_id
                WHERE x.animal_id = ".$animal->getId().$deletedFilterString." ORDER BY measurement_date DESC";
        $retrievedMeasurementData = $this->getConnection()->query($sql)->fetchAll();

        $count = 0;
        foreach ($retrievedMeasurementData as $measurementData)
        {
            $results[$count] = [
                JsonInputConstant::ID => $measurementData[JsonInputConstant::ID],
                JsonInputConstant::MEASUREMENT_DATE => TimeUtil::getDateTimeFromNullCheckedArrayValue(JsonInputConstant::MEASUREMENT_DATE, $measurementData, $nullFiller),
                JsonInputConstant::HEIGHT => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::HEIGHT]),
                JsonInputConstant::KIND => Utils::fillNullOrEmptyString($measurementData[JsonInputConstant::KIND], $nullFiller),
                JsonInputConstant::PROGRESS => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::PROGRESS]),
                JsonInputConstant::SKULL => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::SKULL]),
                JsonInputConstant::MUSCULARITY => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::MUSCULARITY]),
                JsonInputConstant::PROPORTION => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::PROPORTION]),
                JsonInputConstant::EXTERIOR_TYPE => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::EXTERIOR_TYPE]),
                JsonInputConstant::LEG_WORK => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::LEG_WORK]),
                JsonInputConstant::FUR => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::FUR]),
                JsonInputConstant::GENERAL_APPEARANCE => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::GENERAL_APPEARANCE]),
                JsonInputConstant::BREAST_DEPTH => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::BREAST_DEPTH]),
                JsonInputConstant::TORSO_LENGTH => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::TORSO_LENGTH]),
                JsonInputConstant::MARKINGS => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::MARKINGS]),
            ];

            //Only include inspector key if it exists
            $personId = $measurementData[JsonInputConstant::PERSON_ID];
            if($personId != null && $personId != '') {
                $results[$count][JsonInputConstant::INSPECTOR] = [
                    JsonInputConstant::PERSON_ID => $personId,
                    JsonInputConstant::FIRST_NAME => Utils::fillNullOrEmptyString($measurementData[JsonInputConstant::FIRST_NAME], $nullFiller),
                    JsonInputConstant::LAST_NAME => Utils::fillNullOrEmptyString($measurementData[JsonInputConstant::LAST_NAME], $nullFiller),
                    JsonInputConstant::TYPE => "Inspector",
                ];
            }

            $count++;
        }
        return $results;
    }


    /**
     * NOTE! general_appearance is returned spelling corrected as general_appearance
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
                      x.fur, x.general_appearance as general_appearance, x.height, x.breast_depth, x.torso_length, x.markings, x.kind, x.progress, m.measurement_date
                    FROM exterior x
                      INNER JOIN measurement m ON x.id = m.id
                      INNER JOIN (
                                   SELECT animal_id, max(m.measurement_date) as measurement_date
                                   FROM exterior e
                                     INNER JOIN measurement m ON m.id = e.id
                                   WHERE m.is_active = TRUE
                                   GROUP BY animal_id) y on y.animal_id = x.animal_id 
                    WHERE m.measurement_date = y.measurement_date AND m.is_active = TRUE ";

        if(is_int($animalId)) {
            $filter = "AND x.animal_id = " . $animalId;
            $sql = $sqlBase.$filter;
            $result = $this->getConnection()->query($sql)->fetch();
        } else {
            $filter = "";
            $sql = $sqlBase.$filter;
            $result = $this->getConnection()->query($sql)->fetchAll();
        }
        return is_bool($result) && !$result ? $nullResult : $result;
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
            ->where(Criteria::expr()->gte(Variable::MEASUREMENT_DATE, $startTime)) //greater or equal to this startTime
            ->andWhere(Criteria::expr()->lte(Variable::MEASUREMENT_DATE, $endTime)) //less or equal to this endTime
            ->orderBy([Variable::MEASUREMENT_DATE => Criteria::ASC])
        ;

        return $this->getManager()->getRepository(Exterior::class)
            ->matching($criteria);
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
              GROUP BY measurement_date, type, x.animal_id, x.kind, x.skull, x.muscularity, x.progress, x.proportion, x.exterior_type, x.leg_work, x.fur, x.general_appearance, x.height, x.breast_depth, x.torso_length, x.markings 
              HAVING COUNT(*) > 1";
            $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

            foreach ($results as $result) {
                $minId = $result['min_id'];
                $sql = self::DELETE_FROM_EXTERIOR_WHERE_ID . "'".$minId."'";
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

        $exterior1Id = $exterior1[JsonInputConstant::MEASUREMENT_ID];
        $exterior2Id = $exterior2[JsonInputConstant::MEASUREMENT_ID];
        $hasExterior1EmptyHeightKindAndProgress = $this->hasEmptyHeightKindAndProgress($exterior1);
        $hasExterior2EmptyHeightKindAndProgress = $this->hasEmptyHeightKindAndProgress($exterior2);

        if($hasExterior1EmptyHeightKindAndProgress && !$hasExterior2EmptyHeightKindAndProgress){
            if($this->isInspectorNotMissing($exterior2, $exterior1) &&
            $this->areNonHeightKindProgressAndInspectorExteriorValuesIdentical($exterior1, $exterior2)) {
                $sql = self::DELETE_FROM_EXTERIOR_WHERE_ID . $exterior1Id;
                $em->getConnection()->exec($sql);
                $sql = "DELETE FROM measurement WHERE id = ".$exterior1Id;
                $em->getConnection()->exec($sql);
                $hasDeletedAnExterior = true;
                $this->printDeletedRows($exterior1);
            }

        } elseif (!$hasExterior1EmptyHeightKindAndProgress && $hasExterior2EmptyHeightKindAndProgress) {
            if($this->isInspectorNotMissing($exterior1, $exterior2) &&
                $this->areNonHeightKindProgressAndInspectorExteriorValuesIdentical($exterior1, $exterior2)) {
                $sql = self::DELETE_FROM_EXTERIOR_WHERE_ID . $exterior2Id;
                $em->getConnection()->exec($sql);
                $sql = "DELETE FROM measurement WHERE id = ".$exterior2Id;
                $em->getConnection()->exec($sql);
                $hasDeletedAnExterior = true;
                $this->printDeletedRows($exterior2);
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
                $sql = self::DELETE_FROM_EXTERIOR_WHERE_ID . $exterior1Id;
                $em->getConnection()->exec($sql);
                $sql = "DELETE FROM measurement WHERE id = ".$exterior1Id;
                $em->getConnection()->exec($sql);
                $hasDeletedAnExterior = true;
                $this->printDeletedRows($exterior1);
            }

        } elseif (!$hasExterior1EmptyHeightKindAndProgress && $hasExterior2EmptyHeightKindAndProgress) {
            if($inspectorId != null &&
                $this->areNonHeightKindProgressAndInspectorExteriorValuesIdentical($exterior1, $exterior2)) {
                $sql = "UPDATE measurement SET inspector_id = ".$inspectorId." WHERE id = ".$exterior1Id;
                $em->getConnection()->exec($sql);
                $sql = self::DELETE_FROM_EXTERIOR_WHERE_ID . $exterior2Id;
                $em->getConnection()->exec($sql);
                $sql = "DELETE FROM measurement WHERE id = ".$exterior2Id;
                $em->getConnection()->exec($sql);
                $hasDeletedAnExterior = true;
                $this->printDeletedRows($exterior2);
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

        $exterior1Id = $exterior1[JsonInputConstant::MEASUREMENT_ID];
        $exterior2Id = $exterior2[JsonInputConstant::MEASUREMENT_ID];
        $isExterior1ToBeDeleted = $this->areTorsoShiftedPair($exterior2, $exterior1);
        $isExterior2ToBeDeleted = false;
        if(!$isExterior1ToBeDeleted) {
            $isExterior2ToBeDeleted = $this->areTorsoShiftedPair($exterior1, $exterior2);
        }


        if($isExterior1ToBeDeleted){
            $sql = self::DELETE_FROM_EXTERIOR_WHERE_ID . $exterior1Id;
            $em->getConnection()->exec($sql);
            $sql = "DELETE FROM measurement WHERE id = ".$exterior1Id;
            $em->getConnection()->exec($sql);
            $hasDeletedAnExterior = true;
            $this->printDeletedRows($exterior1);

        } elseif ($isExterior2ToBeDeleted) {
            $sql = self::DELETE_FROM_EXTERIOR_WHERE_ID . $exterior2Id;
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
                               GROUP BY m.animal_id_and_date, type, x.animal_id, x.skull, x.muscularity, x.proportion, x.exterior_type, x.leg_work, x.fur, x.general_appearance, x.breast_depth, x.torso_length, x.markings
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
       z.kind as EXT_KIND, z.progress as ONT, z.skull as KOP, muscularity as SPIER, proportion as EVE, exterior_type as TYPE, leg_work as BEEN, fur as VACHT, general_appearance as ALG, height as HOOGTE, breast_depth as BORST_DIEPTE, torso_length as TORSO_LENGTE, markings as KENMERKEN, i.last_name as inspector";
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
            $row = $input[JsonInputConstant::ANIMAL_ID_AND_DATE].
                $input[JsonInputConstant::KIND].
                $input[JsonInputConstant::PROGRESS].
                $input[JsonInputConstant::HEIGHT].
                $input[JsonInputConstant::INSPECTOR_ID];
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
        if($exteriorArrayToDelete[JsonInputConstant::INSPECTOR_ID] == null) {
            return true;
        }
        return $exteriorArrayToKeep[JsonInputConstant::INSPECTOR_ID] != null;
    }


    /**
     * @param array $exterior1
     * @param array $exterior2
     * @return bool
     */
    private function getNonContradictingInspectorIdFromAnyExterior($exterior1, $exterior2)
    {
        $inspectorId1 = $exterior1[JsonInputConstant::INSPECTOR_ID];
        $inspectorId2 = $exterior2[JsonInputConstant::INSPECTOR_ID];

        if($inspectorId1 == $inspectorId2) {
            $result = $inspectorId1;
        } elseif ($inspectorId1 == null && NullChecker::isNotNull($inspectorId2)) {
            $result = $inspectorId2;
        } elseif (NullChecker::isNotNull($inspectorId1) && $inspectorId2 == null) {
            $result = $inspectorId1;
        } else {
            $result = null;
        }
        return $result;
    }


    /**
     * @param array $exterior1
     * @param array $exterior2
     * @return bool
     */
    private function areNonHeightKindProgressAndInspectorExteriorValuesIdentical($exterior1, $exterior2) {

        return
            $exterior1[JsonInputConstant::ANIMAL_ID] == $exterior2[JsonInputConstant::ANIMAL_ID] &&
            $exterior1[JsonInputConstant::ANIMAL_ID_AND_DATE] == $exterior2[JsonInputConstant::ANIMAL_ID_AND_DATE] &&
            $exterior1[JsonInputConstant::MEASUREMENT_DATE] == $exterior2[JsonInputConstant::MEASUREMENT_DATE] &&
            $exterior1[JsonInputConstant::SKULL] == $exterior2[JsonInputConstant::SKULL] &&
            $exterior1[JsonInputConstant::MUSCULARITY] == $exterior2[JsonInputConstant::MUSCULARITY] &&
            $exterior1[JsonInputConstant::PROPORTION] == $exterior2[JsonInputConstant::PROPORTION] &&
            $exterior1[JsonInputConstant::EXTERIOR_TYPE] == $exterior2[JsonInputConstant::EXTERIOR_TYPE] &&
            $exterior1[JsonInputConstant::LEG_WORK] == $exterior2[JsonInputConstant::LEG_WORK] &&
            $exterior1[JsonInputConstant::FUR] == $exterior2[JsonInputConstant::FUR] &&
            $exterior1[JsonInputConstant::GENERAL_APPEARANCE] == $exterior2[JsonInputConstant::GENERAL_APPEARANCE] &&
            $exterior1[JsonInputConstant::BREAST_DEPTH] == $exterior2[JsonInputConstant::BREAST_DEPTH] &&
            $exterior1[JsonInputConstant::TORSO_LENGTH] == $exterior2[JsonInputConstant::TORSO_LENGTH] &&
            $exterior1[JsonInputConstant::MARKINGS] == $exterior2[JsonInputConstant::MARKINGS];
    }


    /**
     * @param array $exteriorArrayToDelete
     * @param array $exteriorToKeep
     * @return bool
     */
    private function areTorsoShiftedPair($exteriorToKeep, $exteriorArrayToDelete) {

        $isDifferencesVerified = 
            NullChecker::numberIsNull($exteriorArrayToDelete[JsonInputConstant::PROGRESS]) &&
            NullChecker::numberIsNull($exteriorArrayToDelete[JsonInputConstant::HEIGHT]) &&
            NullChecker::numberIsNull($exteriorArrayToDelete[JsonInputConstant::TORSO_LENGTH])
            && $exteriorArrayToDelete[JsonInputConstant::BREAST_DEPTH] == $exteriorToKeep[JsonInputConstant::TORSO_LENGTH]
            && $exteriorArrayToDelete['inspector_id'] == null && $exteriorToKeep['inspector_id'] != null;

        $isSimilaritiesVerified =
            $exteriorArrayToDelete[JsonInputConstant::ANIMAL_ID] == $exteriorToKeep[JsonInputConstant::ANIMAL_ID] &&
            $exteriorArrayToDelete[JsonInputConstant::ANIMAL_ID_AND_DATE] == $exteriorToKeep[JsonInputConstant::ANIMAL_ID_AND_DATE] &&
            $exteriorArrayToDelete[JsonInputConstant::MEASUREMENT_DATE] == $exteriorToKeep[JsonInputConstant::MEASUREMENT_DATE] &&
            $exteriorArrayToDelete[JsonInputConstant::SKULL] == $exteriorToKeep[JsonInputConstant::SKULL] &&
            $exteriorArrayToDelete[JsonInputConstant::MUSCULARITY] == $exteriorToKeep[JsonInputConstant::MUSCULARITY] &&
            $exteriorArrayToDelete[JsonInputConstant::PROPORTION] == $exteriorToKeep[JsonInputConstant::PROPORTION] &&
            $exteriorArrayToDelete[JsonInputConstant::EXTERIOR_TYPE] == $exteriorToKeep[JsonInputConstant::EXTERIOR_TYPE] &&
            $exteriorArrayToDelete[JsonInputConstant::LEG_WORK] == $exteriorToKeep[JsonInputConstant::LEG_WORK] &&
            $exteriorArrayToDelete[JsonInputConstant::FUR] == $exteriorToKeep[JsonInputConstant::FUR] &&
            $exteriorArrayToDelete[JsonInputConstant::GENERAL_APPEARANCE] == $exteriorToKeep[JsonInputConstant::GENERAL_APPEARANCE];


        if(NullChecker::isNotNull($exteriorToKeep[JsonInputConstant::KIND])) {
            $isNotMissingExtKind = true;
        } else {
            if(NullChecker::isNull($exteriorArrayToDelete[JsonInputConstant::KIND])) {
                $isNotMissingExtKind = true;
            } else {
                $isNotMissingExtKind = false;
            }
        }

        return $isDifferencesVerified && $isSimilaritiesVerified && $isNotMissingExtKind;
    }
}