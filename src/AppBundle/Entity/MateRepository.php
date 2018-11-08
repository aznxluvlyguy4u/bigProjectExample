<?php

namespace AppBundle\Entity;
use AppBundle\Criteria\MateCriteria;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Output\MateOutput;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

/**
 * Class MateRepository
 * @package AppBundle\Entity
 */
class MateRepository extends BaseRepository {

    /**
     * @param Location $location
     * @param Ewe $mother
     * @return Collection
     */
    public function getMatingFathersOfMother($location, $mother) {
        $em = $this->getEntityManager();
        $queryBuilder = $em->createQueryBuilder();

        $queryBuilder
          ->select('mate')
          ->from ('AppBundle:Mate', 'mate')
          ->where($queryBuilder->expr()->andX(
            $queryBuilder->expr()->andX(
              $queryBuilder->expr()->eq('mate.location', $location->getId()),
              $queryBuilder->expr()->eq('mate.isOverwrittenVersion', 'false'),
              $queryBuilder->expr()->eq('mate.studEwe', $mother->getId()),
              $queryBuilder->expr()->orX(
                $queryBuilder->expr()->isNull('mate.isApprovedByThirdParty'),
                $queryBuilder->expr()->eq('mate.isApprovedByThirdParty', 'true')
              )
            )
          ));

        $query = $queryBuilder->getQuery();
        $result = $query->getResult();
        $fathers = [];

        /** @var Mate $mating */
        foreach ($result as $mating) {
            $fathers[] = $mating->getStudRam();
        }
        
        return $fathers;
    }
    
    /**
     * @param Location $location
     * @param Ewe $mother
     * @return Collection
     */
    public function getMatingsHistory($location)
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
            ->andWhere(Criteria::expr()->orX(
                Criteria::expr()->isNull('isApprovedByThirdParty'),
                Criteria::expr()->eq('isApprovedByThirdParty', true)
            ))
            ->orderBy(['startDate' => Criteria::DESC])
        ;

        return $this->getManager()->getRepository(Mate::class)
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
    
    public function getMatingsByStudIds($studId)
    {

        //First find Matings without a confirmation

            $criteria = Criteria::create()
              ->where(Criteria::expr()->eq('studRam', $studId))
              ->orWhere(Criteria::expr()->eq('studEwe', $studId));

            return self::matching($criteria);
    }

    /**
     * @param Location $location
     * @return Collection
     */
    public function getMatingsErrors($location)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('location', $location))
            ->andWhere(Criteria::expr()->eq('isOverwrittenVersion', false))
            ->andWhere(Criteria::expr()->orX(
                Criteria::expr()->eq('requestState', RequestStateType::FAILED),
                Criteria::expr()->eq('requestState', RequestStateType::CANCELLED),
                Criteria::expr()->eq('requestState', RequestStateType::REJECTED),
                Criteria::expr()->eq('isApprovedByThirdParty', false)
            ))
            ->orderBy(['startDate' => Criteria::DESC])
        ;

        return $this->getManager()->getRepository(Mate::class)
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


    /**
     * @param Location $locationStudRamOwner
     * @return Collection
     */
    public function getMatingsStudRam($locationStudRamOwner)
    {
        //First find Matings without a confirmation
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('requestState', RequestStateType::OPEN))
            ->andWhere(Criteria::expr()->eq('isOverwrittenVersion', false))
            ->andWhere(Criteria::expr()->isNull('isApprovedByThirdParty'))
            ->orderBy(['startDate' => Criteria::DESC])
        ;

        $allMatingsToBeVerified = $this->getManager()->getRepository(Mate::class)
            ->matching($criteria);

        /** @var AnimalRepository $animalRepository */
        $animalRepository = $this->getManager()->getRepository(Animal::class);

        $matingsOfOwner = new ArrayCollection();

        /** @var Mate $mate */
        foreach ($allMatingsToBeVerified as $mate) {
            $ulnCountryCode = $mate->getRamUlnCountryCode();
            $ulnNumber = $mate->getRamUlnNumber();

            $ram = $animalRepository->findByUlnCountryCodeAndNumber($ulnCountryCode, $ulnNumber);

            //Set Ram if missing
            if($mate->getStudRam() == null) {
                $mate->setStudRam($ram);
                $ram->getMatings()->add($mate);
                $this->getManager()->persist($ram);
                $this->getManager()->persist($mate);
                $this->flush();
            }

            if(Validator::isAnimalOfLocation($ram, $locationStudRamOwner)) {
                $matingsOfOwner->add($mate);
            }
        }

        return $matingsOfOwner;
    }

    /**
     * @param Location $locationStudRamOwner
     * @return array
     */
    public function getMatingsStudRamOutput($locationStudRamOwner)
    {
        $matings = $this->getMatingsStudRam($locationStudRamOwner);
        return MateOutput::createMatesStudRamsOverview($matings);
    }


    /**
     * @param Location $location
     * @return \Doctrine\ORM\QueryBuilder
     * @throws \Doctrine\ORM\Query\QueryException
     * @throws \Exception
     */
    public function getQueryBuilderByLocation(Location $location)
    {
        return $this->getManager()->createQueryBuilder()
            ->select('mate')
            ->from(Mate::class, 'mate')
            ->addCriteria(MateCriteria::byLocation($location))
        ;
    }
}