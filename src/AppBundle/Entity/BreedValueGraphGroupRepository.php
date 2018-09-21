<?php


namespace AppBundle\Entity;

/**
 * Class BreedValueGraphGroupRepository
 * @package AppBundle\Entity
 */
class BreedValueGraphGroupRepository extends BaseRepository
{
    /**
     * @return BreedValueGraphGroup[]|array
     */
    public function findAllWithOrdinalAsKey()
    {
        /** @var BreedValueGraphGroup[] $currentGraphGroups */
        $currentGraphGroups = [];
        foreach ($this->findAll() as $group) {
            $currentGraphGroups[$group->getOrdinal()] = $group;
        }

        return $currentGraphGroups;
    }
}