<?php


namespace AppBundle\Filter;


use AppBundle\Entity\Location;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class ActiveLocationFilter extends SQLFilter
{
    const NAME = 'active_location_filter';

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (!($targetEntity instanceof Location)) {
            return '';
        }

        $isActiveColumnName = $targetEntity->getSingleAssociationJoinColumnName('isActive');

        return sprintf('%s.%s = TRUE',
            $targetTableAlias,
            $isActiveColumnName
        );
    }
}