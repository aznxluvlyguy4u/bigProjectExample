<?php

namespace AppBundle\Entity;
use AppBundle\Enumerator\InvoiceStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;

/**
 * Class InvoiceRepository
 * @package AppBundle\Entity
 */
class InvoiceRepository extends BaseRepository
{
    /**
     * @param $ubn
     * @return ArrayCollection
     */
    public function findClientAvailableInvoices($ubn) {
        /** @var QueryBuilder $qb */
        $qb = $this->getEntityManager()->createQueryBuilder();
                $qb->select('i')
                    ->from('AppBundle:Invoice','i')
                    ->where($qb->expr()->andX(
                        $qb->expr()->eq('i.ubn', ':ubn'),
                        $qb->expr()->orX(
                            $qb->expr()->eq('i.status', ':unpaid'),
                            $qb->expr()->eq('i.status', ':paid'),
                            $qb->expr()->eq('i.status', ':cancelled')
                            )
                    ))
                    ->setParameter('ubn', $ubn)
                    ->setParameter('unpaid', InvoiceStatus::UNPAID)
                    ->setParameter('paid', InvoiceStatus::PAID)
                    ->setParameter('cancelled', InvoiceStatus::CANCELLED);
        $result = new ArrayCollection($qb->getQuery()->getResult());
        return $result;
    }

    public function getInvoicesOfCurrentYear($year){
        $qb = $this->getManager()->getRepository(Invoice::class)->createQueryBuilder('qb')
            ->where('qb.invoiceNumber LIKE :year')
            ->orderBy('qb.invoiceNumber', 'DESC')
            ->setMaxResults(1)
            ->setParameter('year', $year."%")
            ->getQuery();
        /** @var Invoice $invoices */
        $result = $qb->getResult();
        return $result;
    }
}