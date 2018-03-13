<?php


namespace AppBundle\Filter;


use AppBundle\Entity\Company;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class ActiveCompanyFilter extends SQLFilter
{
    const NAME = 'active_company_filter';

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (!($targetEntity instanceof Company)) {
            return '';
        }

        $isActiveColumnName = $targetEntity->getSingleAssociationJoinColumnName('isActive');

        return sprintf('%s.%s = TRUE',
            $targetTableAlias,
            $isActiveColumnName
        );
    }
}