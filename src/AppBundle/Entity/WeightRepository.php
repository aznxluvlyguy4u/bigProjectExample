<?php

namespace AppBundle\Entity;
use AppBundle\Util\TimeUtil;
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
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function fixBirthWeightsNotMarkedAsBirthWeight()
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
    public function fixWeightsIncorrectlyMarkedAsBirthWeight()
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
}