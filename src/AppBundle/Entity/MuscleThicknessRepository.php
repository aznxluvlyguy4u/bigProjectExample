<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\model\measurements\MuscleThicknessData;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\DBALException;

/**
 * Class MuscleThicknessRepository
 * @package AppBundle\Entity
 */
class MuscleThicknessRepository extends MeasurementRepository {


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
                  INNER JOIN muscle_thickness t ON t.id = m.id
                  LEFT JOIN person p ON p.id = m.inspector_id
                  INNER JOIN animal a ON a.id = t.animal_id
                WHERE t.animal_id = ".$animal->getId();
        $retrievedMeasurementData = $this->getManager()->getConnection()->query($sql)->fetchAll();

        foreach ($retrievedMeasurementData as $measurementData)
        {
            $results[] = [
                JsonInputConstant::MEASUREMENT_DATE => TimeUtil::getDateTimeFromNullCheckedArrayValue('measurement_date', $measurementData, $nullFiller),
                JsonInputConstant::MUSCLE_THICKNESS => Utils::fillNullOrEmptyString($measurementData['muscle_thickness'], $nullFiller),
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
     * @param Animal $animal
     * @param \DateTime $dateTime
     * @return Collection|MuscleThickness[]
     */
    public function findByAnimalAndDate(Animal $animal, \DateTime $dateTime)
    {
        return $this->getManager()->getRepository(MuscleThickness::class)->matching(
            $this->findByAnimalAndDateCriteria($animal, $dateTime)
        );
    }


    /**
     * @return array
     * @throws DBALException
     */
    public function getContradictingMuscleThicknesses()
    {
        $sql = "
             SELECT
                n.id as id, a.id as animal_id, n.animal_id_and_date, n.measurement_date, n.log_date,
                        z.muscle_thickness, n.inspector_id
             FROM measurement n
              INNER JOIN (
                           SELECT m.animal_id_and_date
                           FROM measurement m
                             INNER JOIN muscle_thickness x ON m.id = x.id
                           WHERE m.is_active
                           GROUP BY m.animal_id_and_date
                           HAVING (COUNT(*) > 1)
                         ) t on t.animal_id_and_date = n.animal_id_and_date
              INNER JOIN muscle_thickness z ON z.id = n.id
              LEFT JOIN person i ON i.id = n.inspector_id
              LEFT JOIN animal a ON a.id = z.animal_id
             WHERE n.is_active";
        $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

        $resultsAsDataObject = array_map(function ($muscleThicknessInArray) {
            return new MuscleThicknessData($muscleThicknessInArray);
        }, $results);

        return $this->groupSqlMeasurementObjectResultsByAnimalIdAndDate($resultsAsDataObject);
    }

}
