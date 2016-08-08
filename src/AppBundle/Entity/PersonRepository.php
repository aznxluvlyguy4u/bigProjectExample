<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class PersonRepository
 * @package AppBundle\Entity
 */
class PersonRepository extends BaseRepository
{

  public function findOneByAccessToken($accessToken)
  {
    $queryBuilder = $this->getEntityManager()->createQueryBuilder();

    $queryBuilder

    ->from('AppBundle:Person', 'person')
      ->select('person')
      ->andWhere('person.accessToken = :accessToken')
      ->setParameter('accessToken',$accessToken);

    return $queryBuilder->getQuery()->getResult();
  }


}