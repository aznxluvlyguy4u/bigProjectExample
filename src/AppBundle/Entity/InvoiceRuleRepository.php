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
                $qb->expr()->eq("q.type", ":type")
            );
            $qb->setParameter("type", $type);
        }

        if ($activeOnly) {
            $qb->andWhere(
                $qb->expr()->eq('q.isDeleted', 'false')
            );
        }

        return $qb->getQuery()->getResult();
    }


}