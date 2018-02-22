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
     * @return array
     */
    public function findByType($type)
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

        return $qb->getQuery()->getResult();
    }


}