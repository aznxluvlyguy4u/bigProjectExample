<?php

namespace AppBundle\Entity;

use AppBundle\Constant\MeasurementConstant;
use AppBundle\Util\MeasurementsUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

class MeasurementRepository extends BaseRepository {

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
        $logDateString = TimeUtil::getTimeStampNow('Y-m-d H:i:s');

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
}