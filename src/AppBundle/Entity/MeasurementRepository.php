<?php

namespace AppBundle\Entity;

use AppBundle\Constant\MeasurementConstant;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\MeasurementsUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

class MeasurementRepository extends BaseRepository {

    const UPDATE_BATCH_SIZE = 10000;

    /**
     * @param int $startYear
     * @param int $endYear
     * @return Collection
     */
    public function getMeasurementsBetweenYears($startYear, $endYear)
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

        $measurements = $this->getManager()->getRepository(Measurement::class)
        ->matching($criteria);

        return $measurements;
    }


    /**
     * @param array $results
     * @return array
     */
    protected function groupSqlMeasurementResultsByAnimalIdAndDate($results)
    {
        $measurementsGroupedByAnimalAndDate = array();
        foreach ($results as $result) {
            $animalIdAndData = $result['animal_id_and_date'];
            if(array_key_exists($animalIdAndData, $measurementsGroupedByAnimalAndDate)) {
                $items = $measurementsGroupedByAnimalAndDate[$animalIdAndData];
                $items->add($result);
                $measurementsGroupedByAnimalAndDate[$animalIdAndData] = $items;
            } else {
                //First entry
                $items = new ArrayCollection();
                $items->add($result);
                $measurementsGroupedByAnimalAndDate[$animalIdAndData] = $items;
            }
        }
        return $measurementsGroupedByAnimalAndDate;
    }


    /**
     * @param string $animalIdAndDate
     * @param string $measurementDateString
     * @param string $type
     * @param int $inspectorId
     * @return bool
     */
    protected function insertNewMeasurementInParentTable($animalIdAndDate, $measurementDateString, $type, $inspectorId)
    {
        $logDateString = TimeUtil::getLogDateString();

        $isInsertSuccessful = false;
        if(NullChecker::isNotNull($measurementDateString) && NullChecker::isNotNull($animalIdAndDate) && NullChecker::isNotNull($type)) {
            if(MeasurementsUtil::isValidMeasurementType($type) && TimeUtil::isFormatYYYYMMDD($measurementDateString)) {

                if(NullChecker::isNull($inspectorId)) {
                    $inspectorId = 'NULL';
                }

                $sql = "INSERT INTO measurement (id, log_date, measurement_date, type, inspector_id, animal_id_and_date) VALUES (nextval('measurement_id_seq'),'" .$logDateString. "','" . $measurementDateString . "','".$type."', ".$inspectorId.",'".$animalIdAndDate."')";
                $this->getManager()->getConnection()->exec($sql);

                $isInsertSuccessful = true;

            }
        }
        return $isInsertSuccessful;
    }


    /**
     * @return int|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getMaxId()
    {
        $sql = "SELECT MAX(id) FROM measurement";
        return $this->executeSqlQuery($sql);
    }

    
    public function removeTimeFromAllMeasurementDates()
    {
        $sql = "UPDATE measurement SET measurement_date = DATE(measurement_date)";
        $this->getManager()->getConnection()->exec($sql);
    }


    /**
     * @param CommandUtil|null $cmdUtil
     * @throws \Doctrine\DBAL\DBALException
     */
    public function setAnimalIdAndDateValues(CommandUtil $cmdUtil = null)
    {
        //Retrieve the mismatched measurementRecords
        $sql = "SELECT m.id, CONCAT(x.animal_id,'_',DATE(measurement_date)) as animal_id_and_date, m.type FROM measurement m
                  INNER JOIN exterior x ON x.id = m.id
                WHERE m.animal_id_and_date <> CONCAT(x.animal_id,'_',DATE(measurement_date))
                UNION
                SELECT m.id, CONCAT(x.animal_id,'_',DATE(measurement_date)) as animal_id_and_date, m.type FROM measurement m
                  INNER JOIN body_fat x ON x.id = m.id
                WHERE m.animal_id_and_date <> CONCAT(x.animal_id,'_',DATE(measurement_date))
                UNION
                SELECT m.id, CONCAT(x.animal_id,'_',DATE(measurement_date)) as animal_id_and_date, m.type FROM measurement m
                  INNER JOIN weight x ON x.id = m.id
                WHERE m.animal_id_and_date <> CONCAT(x.animal_id,'_',DATE(measurement_date))
                UNION
                SELECT m.id, CONCAT(x.animal_id,'_',DATE(measurement_date)) as animal_id_and_date, m.type FROM measurement m
                  INNER JOIN muscle_thickness x ON x.id = m.id
                WHERE m.animal_id_and_date <> CONCAT(x.animal_id,'_',DATE(measurement_date))
                UNION
                SELECT m.id, CONCAT(x.animal_id,'_',DATE(measurement_date)) as animal_id_and_date, m.type FROM measurement m
                  INNER JOIN tail_length x ON x.id = m.id
                WHERE m.animal_id_and_date <> CONCAT(x.animal_id,'_',DATE(measurement_date))
                ORDER BY id";
        $results = $this->getConnection()->query($sql)->fetchAll();
        $totalCount = count($results);

        if($totalCount == 0) {
            $cmdUtil->writeln('All animalIdAndDate values match!');
            return;
        }

        $updateString = '';
        $count = 0;
        $inBatchCount = 0;
        $updatedCount = 0;

        if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt($totalCount, 1); }

        foreach ($results as $result) {
            $id = $result['id'];
            $animalIdAndDate = $result['animal_id_and_date'];

            $updateString = $updateString."('".$animalIdAndDate."',".$id.")";
            $count++;
            if($count == $totalCount || $count%self::UPDATE_BATCH_SIZE == 0) {
                $sql = "UPDATE measurement as m SET animal_id_and_date = v.animal_id_and_date
						FROM (VALUES ".$updateString."
							 ) as v(animal_id_and_date, id) WHERE m.id = v.id";
                $this->getConnection()->exec($sql);
                //Reset batch string and counters
                $updateString = '';
                $updatedCount += $inBatchCount;
                $inBatchCount = 0;
            } else {
                $inBatchCount++;
                $updateString = $updateString.',';
            }
            if($cmdUtil != null) { $cmdUtil->advanceProgressBar(1, 'animalIdAndDate in measurement updated|inBatch: '.$updatedCount.'|'.$inBatchCount); }
        }
        if($cmdUtil != null) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }
    }
}