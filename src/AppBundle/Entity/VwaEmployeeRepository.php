<?php


namespace AppBundle\Entity;
use Doctrine\Common\Collections\Criteria;

/**
 * Class VwaEmployeeRepository
 * @package AppBundle\Entity
 */
class VwaEmployeeRepository extends BaseRepository
{
    /**
     * @return array|VwaEmployee[]
     */
    public function findActiveOnly()
    {
        return $this->findBy(['isActive' => true], ['lastName' => Criteria::ASC, 'firstName' => Criteria::ASC]);
    }
}