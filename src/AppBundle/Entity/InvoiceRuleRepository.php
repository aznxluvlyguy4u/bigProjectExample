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
}