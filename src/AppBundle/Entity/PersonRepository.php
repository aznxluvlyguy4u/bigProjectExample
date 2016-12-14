<?php

namespace AppBundle\Entity;

use AppBundle\Component\Utils;
use AppBundle\Enumerator\PersonType;
use AppBundle\Util\NullChecker;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
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
   * @param string $emailAddress
   * @return int
   */
  protected function insertNewPersonParentTable($type, $firstName, $lastName, $isActive = true, $emailAddress = '')
  {
    $id = 0;
    if(PersonType::isValidType($type) && $firstName !== null && NullChecker::isNotNull($lastName)) {
      $password = self::DEFAULT_BLANK_PASSWORD;
      $personId = Utils::generatePersonId();
      $tokenCode = Utils::generateTokenCode();

      $isActive = StringUtil::getBooleanAsString($isActive);

      $sql = "INSERT INTO person (id, first_name, last_name, type, is_active, password, person_id, email_address) VALUES (nextval('person_id_seq'),'" .$firstName. "','" . $lastName . "','".$type."',".$isActive.",'".$password."','".$personId."','".$emailAddress."') RETURNING id";
      $id = $this->getConnection()->query($sql)->fetch()['id'];

      $dateString = TimeUtil::getLogDateString();

      //Insert AccessToken
      $sql = "INSERT INTO token (id, owner_id, code, type, creation_date_time, is_verified) VALUES (nextval('token_id_seq'),'" 
          .$id. "','" . $tokenCode . "','ACCESS','".$dateString."',TRUE)";
      $this->getConnection()->exec($sql);
      
      $isInsertSuccessFul = true;
    }
    return $id;
  }
}