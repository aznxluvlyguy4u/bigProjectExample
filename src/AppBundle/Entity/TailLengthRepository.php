<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use Doctrine\Common\Collections\Criteria;

/**
 * Class TailLengthRepository
 * @package AppBundle\Entity
 */
class TailLengthRepository extends MeasurementRepository {

    /**
     * @param Animal $animal
     * @param string $nullFiller
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
                  INNER JOIN tail_length t ON t.id = m.id
                  LEFT JOIN person p ON p.id = m.inspector_id
                  INNER JOIN animal a ON a.id = t.animal_id
                WHERE t.animal_id = ".$animal->getId();
        $retrievedMeasurementData = $this->getManager()->getConnection()->query($sql)->fetchAll();

        foreach ($retrievedMeasurementData as $measurementData)
        {
            $results[] = [
                JsonInputConstant::MEASUREMENT_DATE => Utils::fillNullOrEmptyString($measurementData['measurement_date'], $nullFiller),
                JsonInputConstant::LENGTH => Utils::fillNullOrEmptyString($measurementData['length'], $nullFiller),
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
    public function getLatestTailLength(Animal $animal)
    {
        //Measurement Criteria
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('animal', $animal))
            ->orderBy(['measurementDate' => Criteria::DESC])
            ->setMaxResults(1);

        //TailLength
        $latestTailLength = $this->getManager()->getRepository(TailLength::class)
            ->matching($criteria);

        if(sizeof($latestTailLength) > 0) {
            $latestTailLength = $latestTailLength->get(0);
            $latestTailLength = $latestTailLength->getLength();
        } else {
            $latestTailLength = 0.00;
        }
        return $latestTailLength;
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
              SELECT MIN(measurement.id) as min_id, COUNT(*), measurement_date, animal_id, length
              FROM measurement INNER JOIN tail_length x ON measurement.id = x.id
              GROUP BY measurement_date, type, x.animal_id, x.length
              HAVING COUNT(*) > 1";
            $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

            foreach ($results as $result) {
                $minId = $result['min_id'];
                $sql = "DELETE FROM tail_length WHERE id = '".$minId."'";
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
    public function getContradictingTailLengthsForExportFile()
    {
        $sql = "
             SELECT i.last_name as inspector, n.id as metingId, a.id as animal_id, DATE(n.measurement_date) as meetdatum, z.length as staartlengte, CONCAT(a.uln_country_code, a.uln_number) as uln, CONCAT(a.pedigree_country_code, a.pedigree_number) as stn, DATE(a.date_of_birth) as geboortedatum FROM measurement n
              INNER JOIN (
                           SELECT m.animal_id_and_date
                           FROM measurement m
                             INNER JOIN tail_length x ON m.id = x.id
                           GROUP BY m.animal_id_and_date
                           HAVING (COUNT(*) > 1)
                         ) t on t.animal_id_and_date = n.animal_id_and_date
              INNER JOIN tail_length z ON z.id = n.id
              LEFT JOIN person i ON i.id = n.inspector_id
              LEFT JOIN animal a ON a.id = z.animal_id";
        return  $this->getManager()->getConnection()->query($sql)->fetchAll();
    }
}