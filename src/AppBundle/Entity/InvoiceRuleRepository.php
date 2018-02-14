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
     * @param $type
     * @param $category
     * @return array
     */
    public function findByTypeCategory($type, $category) {
        $qb = $this->_em->createQueryBuilder();
        $qb->select("q")
            ->from(InvoiceRule::class, "q");
        if ($type != null) {
            $qb->andWhere(
                $qb->expr()->eq("q.type", ":type")
            );
            $qb->setParameter("type", $type);
        }
        if ($category != null) {
            $qb->andWhere(
                $qb->expr()->eq("q.category", ":category")
            );
            $qb->setParameter("category", $category);
        }
        return $qb->getQuery()->getResult();
    }
}