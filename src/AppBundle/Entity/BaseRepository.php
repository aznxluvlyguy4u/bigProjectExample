<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

class BaseRepository extends EntityRepository
{
    public function persist($entity)
    {
        $this->getEntityManager()->persist($entity);
        $this->update($entity);

        return $entity;
    }

    public function update($entity)
    {
        $this->getEntityManager()->flush($entity);

        return $entity;
    }
}
