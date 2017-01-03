<?php

namespace AppBundle\Entity;

use AppBundle\Component\Utils;
use AppBundle\Enumerator\PersonType;
use AppBundle\Util\NullChecker;
use AppBundle\Util\StringUtil;
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
  const DEFAULT_BLANK_PASSWORD = 'BLANK';

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


  /**
   * @param $lastName
   */
  public function findFirstIdByLastName($lastName)
  {
    $sql = "SELECT id FROM person WHERE last_name = '".$lastName."'";
    $result = $this->getManager()->getConnection()->query($sql)->fetch();
    return $result['id'];
  }


  /**
   * @param int $id
   * @return string|null
   */
  public function getLastNameById($id)
  {
    if($id == null || strtoupper($id) == 'NULL') { return null; }

    $sql = "SELECT last_name FROM person WHERE id = ".$id;
    $result = $this->getManager()->getConnection()->query($sql)->fetch();
    return $result['last_name'];
  }


  /**
   * @param string $type
   * @param string $firstName
   * @param string $lastName
   * @param bool $isActive
   * @return bool
   */
  protected function insertNewPersonParentTable($type, $firstName, $lastName, $isActive = true)
  {
    $isInsertSuccessFul = false;
    if(PersonType::isValidType($type) && NullChecker::isNotNull($lastName)) {
      if($firstName == null) { $firstName = ''; }
      
      $password = self::DEFAULT_BLANK_PASSWORD;
      $personId = Utils::generatePersonId();

      $isActive = StringUtil::getBooleanAsString($isActive);
      
      $sql = "INSERT INTO person (id, first_name, last_name, type, is_active, password, person_id) VALUES (nextval('person_id_seq'),'" .$firstName. "','" . $lastName . "','".$type."',".$isActive.",'".$password."','".$personId."')";
      $this->getManager()->getConnection()->exec($sql);
      $isInsertSuccessFul = true;
    }
    return $isInsertSuccessFul;
  }
}