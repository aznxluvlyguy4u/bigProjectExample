<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\ORM\NonUniqueResultException;

/**
 * Class CompanyRepository
 * @package AppBundle\Entity
 */
class CompanyRepository extends BaseRepository
{
    /**
     * @throws NonUniqueResultException
     */
    public function getLatestDebtorNumberOrdinal()
    {
        $currentDate = new DateTime();
        $currentYear = $currentDate->format('Y');

        $res = $this->createQueryBuilder('company')
            ->select('company.debtorNumberOrdinal')
            ->where('company.debtorNumberYear = :year')
            ->andWhere('company.debtorNumberOrdinal is not NULL')
            ->orderBy('company.id', 'DESC')
            ->groupBy('company.debtorNumberOrdinal, company.id')
            ->setParameter('year', $currentYear)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($res !== null) {
            return $res['debtorNumberOrdinal'];
        }

        return null;
    }
}