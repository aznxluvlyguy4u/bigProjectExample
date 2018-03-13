<?php


namespace AppBundle\Filter;


use AppBundle\Entity\Invoice;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class ActiveInvoiceFilter extends SQLFilter
{
    const NAME = 'active_invoice_filter';

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (!($targetEntity instanceof Invoice)) {
            return '';
        }

        $isDeletedColumnName = $targetEntity->getSingleAssociationJoinColumnName('isDeleted');

        return sprintf('%s.%s = FALSE',
            $targetTableAlias,
            $isDeletedColumnName
        );
    }
}