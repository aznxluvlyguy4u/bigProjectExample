<?php

namespace AppBundle\Entity;

/**
 * Class TreatmentRepository
 * @package AppBundle\Entity
 */
class TreatmentRepository extends BaseRepository {

    public function getHistoricTreatments($ubn)
    {
        return $this->createQueryBuilder('treatment')
         ->innerJoin('treatment.location', 'location')
         ->where('location.ubn = :ubn')
         ->setParameter('ubn', $ubn)
         ->getQuery()
         ->getResult();
    }
}