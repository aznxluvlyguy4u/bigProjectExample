<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\TreatmentTypeOption;
use AppBundle\Util\ArrayUtil;

class TreatmentTemplateRepository extends BaseRepository
{
    /**
     * @param Location $location
     * @param boolean $activeOnly
     * @return array
     */
    public function findAllBelongingToLocation($location, $activeOnly)
    {
        $filter =
            [
                'location' => $location,
            ];

        if ($activeOnly) {
            $filter['isActive'] = true;
        }

        $qb = $this->getManager()->createQueryBuilder();
        $qb
            ->select('template')
            ->from(TreatmentTemplate::class, 'template')
            ->where($qb->expr()->orX(
                $qb->expr()->eq('template.location', $location->getId()),
                $qb->expr()->isNull('template.location')
            ));

        if ($activeOnly) {
            $qb->andWhere($qb->expr()->eq('template.isActive', 'true'));
        }

        $qb->orderBy('template.id' ,'DESC');
        $query = $qb->getQuery();


        return $query->getResult();
    }


    /**
     * @param Location $location
     * @param boolean $activeOnly
     * @return array
     */
    public function findIndividualTypeByLocation($location, $activeOnly)
    {
        return $this->findActiveByLocation($location, TreatmentTypeOption::INDIVIDUAL, $activeOnly);
    }


    /**
     * @param Location $location
     * @param boolean $activeOnly
     * @return array
     */
    public function findLocationTypeByLocation($location, $activeOnly)
    {
        return $this->findActiveByLocation($location, TreatmentTypeOption::LOCATION, $activeOnly);
    }


    public function findActiveByLocation($location, $type, $activeOnly)
    {
        $filter =
            [
                'type' => $type,
                'location' => $location,
            ];

        if ($activeOnly) {
            $filter['isActive'] = true;
        }

        return $this->findBy($filter, ['logDate' => 'DESC']);
    }
}
