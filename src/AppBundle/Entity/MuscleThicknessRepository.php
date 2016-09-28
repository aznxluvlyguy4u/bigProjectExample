<?php

namespace AppBundle\Entity;
use AppBundle\Constant\MeasurementConstant;
use AppBundle\Enumerator\MeasurementType;
use AppBundle\Util\MeasurementsUtil;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Collections\Criteria;

/**
 * Class MuscleThicknessRepository
 * @package AppBundle\Entity
 */
class MuscleThicknessRepository extends MeasurementRepository {

    /**
     * @param Animal $animal
     * @return float
     */
    public function getLatestMuscleThickness(Animal $animal)
    {
        //Measurement Criteria
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('animal', $animal))
            ->orderBy(['measurementDate' => Criteria::DESC])
            ->setMaxResults(1);

        //MuscleThickness
        $latestMuscleThickness = $this->getManager()->getRepository(MuscleThickness::class)
            ->matching($criteria);

        if(sizeof($latestMuscleThickness) > 0) {
            $latestMuscleThickness = $latestMuscleThickness->get(0);
            $latestMuscleThickness = $latestMuscleThickness->getMuscleThickness();
        } else {
            $latestMuscleThickness = 0.0;
        }

        return $latestMuscleThickness;
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
              SELECT MIN(measurement.id) as min_id, COUNT(*), measurement_date, animal_id, muscle_thickness
              FROM measurement INNER JOIN muscle_thickness x ON measurement.id = x.id
              GROUP BY measurement_date, type, x.animal_id, x.muscle_thickness
              HAVING COUNT(*) > 1";
            $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

            foreach ($results as $result) {
                $minId = $result['min_id'];
                $sql = "DELETE FROM muscle_thickness WHERE id = '".$minId."'";
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
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getContradictingMuscleThicknessesForExportFile()
    {
        $sql = "
             SELECT i.last_name as inspector, n.id as metingId, a.id as animal_id, DATE(n.measurement_date) as meetdatum, z.muscle_thickness as spierdikte, CONCAT(a.uln_country_code, a.uln_number) as uln, CONCAT(a.pedigree_country_code, a.pedigree_number) as stn, DATE(a.date_of_birth) as geboortedatum FROM measurement n
              INNER JOIN (
                           SELECT m.animal_id_and_date
                           FROM measurement m
                             INNER JOIN muscle_thickness x ON m.id = x.id
                           GROUP BY m.animal_id_and_date
                           HAVING (COUNT(*) > 1)
                         ) t on t.animal_id_and_date = n.animal_id_and_date
              INNER JOIN muscle_thickness z ON z.id = n.id
              LEFT JOIN person i ON i.id = n.inspector_id
              LEFT JOIN animal a ON a.id = z.animal_id";
        return  $this->getManager()->getConnection()->query($sql)->fetchAll();
    }


    /**
     * @param boolean $isGetGroupedByAnimalAndDate
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getAllMuscleThicknessesBySql($isGetGroupedByAnimalAndDate = false)
    {
        $sql = "
             SELECT n.*, z.*, CONCAT(a.uln_country_code, a.uln_number) as uln, CONCAT(a.pedigree_country_code, a.pedigree_number) as stn, p.last_name as inspector_last_name FROM measurement n

              INNER JOIN muscle_thickness z ON z.id = n.id
              LEFT JOIN person p ON p.id = n.inspector_id
              LEFT JOIN animal a ON a.id = z.animal_id";
        $results =  $this->getManager()->getConnection()->query($sql)->fetchAll();

        if($isGetGroupedByAnimalAndDate) {
            return $this->groupSqlMeasurementResultsByAnimalIdAndDate($results);
        } else {
            return $results;
        }
    }


    /**
     * @param string $animalIdAndDate
     * @param int $inspectorId
     * @param float $muscleThicknessValue
     * @return bool
     */
    public function insertNewMuscleThickness($animalIdAndDate, $muscleThicknessValue, $inspectorId = null)
    {
        $parts = MeasurementsUtil::getIdAndDateFromAnimalIdAndDateString($animalIdAndDate);
        $animalId = $parts[MeasurementConstant::ANIMAL_ID];
        $measurementDateString = $parts[MeasurementConstant::DATE];

        $isInsertSuccessful = false;
        $isInsertParentSuccessful = $this->insertNewMeasurementInParentTable($animalIdAndDate, $measurementDateString, MeasurementType::MUSCLE_THICKNESS, $inspectorId);
        if($isInsertParentSuccessful && NullChecker::floatIsNotZero($muscleThicknessValue)) {
            $sql = "INSERT INTO muscle_thickness (id, animal_id, muscle_thickness) VALUES (currval('measurement_id_seq'),'".$animalId."','".$muscleThicknessValue."')";
            $this->getManager()->getConnection()->exec($sql);
            $isInsertSuccessful = true;
        }
        return $isInsertSuccessful;
    }
}