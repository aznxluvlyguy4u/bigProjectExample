<?php

namespace AppBundle\Entity;

/**
 * Class InvoiceRuleRepository
 * @package AppBundle\Entity
 */
class InvoiceRuleRepository extends BaseRepository
{

    /**
     * @return array
     */
    public function findAll()
    {
        return $this->findBy([],['sortOrder' => 'ASC', 'category' => 'ASC', 'description' => 'ASC']);
    }


    /**
     * @param string $type
     * @param boolean $activeOnly
     * @return array
     */
    public function findByType($type, $activeOnly)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select("q")
            ->from(InvoiceRule::class, "q");

        if ($type != null) {
            $qb->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->eq("q.type", ":type"),
                    $qb->expr()->eq("q.isBatch", 'false')
                )
            );
            $qb->setParameter("type", $type);
        }

        if ($activeOnly) {
            $qb->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->eq('q.isDeleted', 'false'),
                    $qb->expr()->eq("q.isBatch", 'false')
                )
            );
        }

        return $qb->getQuery()->getResult();
    }


    public function findBatchRules() {
        $qb = $this->_em->createQueryBuilder();
        $qb->select("ir")
            ->from(InvoiceRule::class, "ir")
            ->where(
                $qb->expr()->eq("ir.isBatch", "true")
            );

        return $qb->getQuery()->getResult();
    }

}