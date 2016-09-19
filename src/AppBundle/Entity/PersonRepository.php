<?php

namespace AppBundle\Entity;

use Doctrine\Common\Persistence\ObjectManager;
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
    $queryBuilder = $this->getManager()->createQueryBuilder();

    $queryBuilder

    ->from('AppBundle:Person', 'person')
      ->select('person')
      ->andWhere('person.accessToken = :accessToken')
      ->setParameter('accessToken',$accessToken);

    return $queryBuilder->getQuery()->getResult();
  }


}