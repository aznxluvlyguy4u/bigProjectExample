<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\RequestStateType;
use AppBundle\Output\DeclareWeightOutput;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

/**
 * Class DeclareWeightRepository
 * @package AppBundle\Entity
 */
class DeclareWeightRepository extends BaseRepository {

    /**
     * @param Location $location
     * @return Collection
     */
    public function getDeclareWeightsHistory($location)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('location', $location))
            ->andWhere(Criteria::expr()->eq('isOverwrittenVersion', false))
            ->andWhere(Criteria::expr()->orX(
                Criteria::expr()->eq('requestState', RequestStateType::FINISHED),
                Criteria::expr()->eq('requestState', RequestStateType::OPEN),
                Criteria::expr()->eq('requestState', RequestStateType::REVOKED),
                Criteria::expr()->eq('requestState', RequestStateType::REVOKING)
            ))
            ->orderBy(['logDate' => Criteria::DESC])
        ;

        return $this->getEntityManager()->getRepository(DeclareWeight::class)
            ->matching($criteria);
    }

    /**
     * @param Location $location
     * @return array
     */
    public function getDeclareWeightsHistoryOutput($location)
    {
        $declareWeights = $this->getDeclareWeightsHistory($location);
        return DeclareWeightOutput::createDeclareWeightsOverview($declareWeights);
    }

}