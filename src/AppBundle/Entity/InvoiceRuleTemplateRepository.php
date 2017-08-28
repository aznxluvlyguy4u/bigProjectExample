<?php

namespace AppBundle\Entity;

/**
 * Class InvoiceRuleTemplateRepository
 * @package AppBundle\Entity
 */
class InvoiceRuleTemplateRepository extends BaseRepository
{
    /**
     * @return array
     */
    public function findAll()
    {
        return $this->findBy([],['category' => 'ASC', 'description' => 'ASC']);
    }
}