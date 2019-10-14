<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\Variable;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * Class AnimalResidenceRepository
 * @package AppBundle\Entity
 */
class AnimalResidenceRepository extends BaseRepository {


    /**
     * @param Location $location
     * @param Animal $animal
     * @param \DateTime $endDate
     * @return null|AnimalResidence
     */
    public function getByEndDate(Location $location, Animal $animal, \DateTime $endDate)
    {
        if (!$location || !$animal || !$endDate) {
            return;
        }

        return $this->findOneBy([
            Variable::LOCATION => $location,
            Variable::ANIMAL => $animal,
            Variable::END_DATE => $endDate
        ]);
    }

    public function getLastByNullEndDate(Animal $animal)
    {
        $results = $this->findBy(array(Variable::END_DATE => null, 'animal_id' => $animal->getId()));

        if(sizeof($results) == 0) {
            return null;

        } else if(sizeof($results) == 1) {
            return $results[0];

        } else { //if(sizeof($results) > 1)
            return Utils::returnLastAnimalResidenceByStartDate($results);
        }
    }


    /**
     * @param Location $location
     * @param Animal $animal
     * @return AnimalResidence|null
     */
    public function getLastResidenceOnLocation($location, $animal)
    {
        if(! ($location instanceof Location && $animal instanceof Animal) ) {
            return null;
        }

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq(Variable::LOCATION, $location))
            ->andWhere(Criteria::expr()->eq(Variable::ANIMAL, $animal))
            ->orderBy([Variable::START_DATE => Criteria::DESC, Variable::LOG_DATE => Criteria::DESC])
            ->setMaxResults(1);

        /** @var ArrayCollection $results */
        $results = $this->getManager()->getRepository(AnimalResidence::class)
            ->matching($criteria);

        if($results->count() > 0) {
            $lastResidence = $results->get(0);
        } else {
            $lastResidence = null;
        }

        return $lastResidence;
    }


    /**
     * @param Location $location
     * @param Animal $animal
     * @return AnimalResidence|null
     */
    public function getLastOpenResidenceOnLocation($location, $animal)
    {
        if(! ($location instanceof Location && $animal instanceof Animal) ) {
            return null;
        }

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq(Variable::LOCATION, $location))
            ->andWhere(Criteria::expr()->eq(Variable::ANIMAL, $animal))
            ->andWhere(Criteria::expr()->isNull(Variable::END_DATE))
            ->orderBy([Variable::START_DATE => Criteria::DESC, Variable::LOG_DATE => Criteria::DESC])
            ->setMaxResults(1);

        /** @var ArrayCollection $results */
        $results = $this->getManager()->getRepository(AnimalResidence::class)
            ->matching($criteria);

        if($results->count() > 0) {
            $lastResidence = $results->get(0);
        } else {
            $lastResidence = null;
        }

        return $lastResidence;
    }


    /**
     * @param $animalIds
     * @return int
     */
    public function deleteByAnimalIdsAndSql($animalIds)
    {
        return $this->deleteTableRecordsByTableNameAndAnimalIdsAndSql('animal_residence', $animalIds);
    }


    /**
     * @param int $animalId
     * @param int $locationId
     * @param string|null $startDateString
     * @param string|null $endDateString
     * @param string $logDateString
     * @param bool $isPending
     * @param string $country
     * @return string
     */
    public function getSqlInsertString($animalId, $locationId, $startDateString, $endDateString, $logDateString, $isPending = false, $country = 'NL')
    {
        return "INSERT INTO animal_residence (id, animal_id, log_date, start_date, end_date, is_pending, country, location_id
							)VALUES(nextval('animal_residence_id_seq'),".$animalId.",'".$logDateString."',".
        SqlUtil::getNullCheckedValueForSqlQuery($startDateString, true).",".SqlUtil::getNullCheckedValueForSqlQuery($endDateString, true).",".StringUtil::getBooleanAsString($isPending).",'".$country."',".$locationId.")";
    }


    /**
     * @param array $animalIds
     * @return array|AnimalResidence[]
     */
    public function getByAnimalIds($animalIds = [])
    {
        if (empty($animalIds)) {
            return [];
        }


        $qb = $this->getManager()->createQueryBuilder();

        $qb
            ->select('r')
            ->from (AnimalResidence::class, 'r')
        ;

        foreach ($animalIds as $animalId) {
            $qb->orWhere($qb->expr()->eq('r.animal', $animalId));
        }

        $qb
            ->orderBy('r.animal' ,'ASC')
            ->addOrderBy('r.startDate', 'ASC')
            ->addOrderBy('r.endDate', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }
}