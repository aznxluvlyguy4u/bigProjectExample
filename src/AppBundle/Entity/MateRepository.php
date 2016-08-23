<?php

namespace AppBundle\Entity;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Output\MateOutput;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

/**
 * Class MateRepository
 * @package AppBundle\Entity
 */
class MateRepository extends BaseRepository {

    /**
     * @param Location $location
     * @return Collection
     */
    public function getMatingsHistory($location)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('location', $location))
            ->andWhere(Criteria::expr()->eq('requestState', RequestStateType::FINISHED))
            ->orWhere(Criteria::expr()->eq('requestState', RequestStateType::OPEN))
            ->orWhere(Criteria::expr()->eq('requestState', RequestStateType::REVOKED))
            ->orWhere(Criteria::expr()->eq('requestState', RequestStateType::REVOKING))
            ->andWhere(Criteria::expr()->isNull('isAcceptedByThirdParty'))
            ->orWhere(Criteria::expr()->eq('isAcceptedByThirdParty', true))
            ->orderBy(['startDate' => Criteria::DESC])
        ;

        return $this->getEntityManager()->getRepository(Mate::class)
            ->matching($criteria);
    }

    /**
     * @param Location $location
     * @return array
     */
    public function getMatingsHistoryOutput($location)
    {
        $matings = $this->getMatingsHistory($location);
        return MateOutput::createMatesOverview($matings);
    }
    


    /**
     * @param Location $location
     * @return Collection
     */
    public function getMatingsErrors($location)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('location', $location))
            ->andWhere(Criteria::expr()->eq('requestState', RequestStateType::CANCELLED))
            ->orWhere(Criteria::expr()->eq('requestState', RequestStateType::REJECTED))
            ->orWhere(Criteria::expr()->eq('isAcceptedByThirdParty', false))
            ->orderBy(['startDate' => Criteria::DESC])
        ;

        return $this->getEntityManager()->getRepository(Mate::class)
            ->matching($criteria);
    }

    /**
     * @param Location $location
     * @return array
     */
    public function getMatingsErrorOutput($location)
    {
        $matings = $this->getMatingsErrors($location);
        return MateOutput::createMatesOverview($matings);
    }
}