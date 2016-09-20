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
        $weightMeasurements = $this->getManager()->getRepository(Weight::class)
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
}