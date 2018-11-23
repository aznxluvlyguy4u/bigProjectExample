<?php

namespace AppBundle\Entity;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\Mapping\ClassMetadata;

class TagRepository extends BaseRepository {

  /**
   * @param Client $client
   * @param string $ulnString
   * @return Tag|null
   */
  public function findOneByString(Client $client, $ulnString)
  {
    //Verify format first
    if(!Validator::verifyUlnFormat($ulnString)) {
      return null;
    }
    $uln = Utils::getUlnFromString($ulnString);

    return $this->findOneByUln($client, $uln[Constant::ULN_COUNTRY_CODE_NAMESPACE], $uln[Constant::ULN_NUMBER_NAMESPACE]);
  }

  /**
   * @param Client $client
   * @param $countryCode
   * @param $ulnNumber
   * @return Tag|null
   */
  public function findOneByUln(Client $client, $countryCode, $ulnNumber)
  {
    foreach($client->getTags() as $tag){
      if($tag->getUlnCountryCode() == $countryCode && $tag->getUlnNumber() == $ulnNumber) {
        return $tag; //assuming uln is unique
      }
    }
    return null;
  }


  /**
   * @param $ulnCountryCode
   * @param $ulnNumber
   * @return ArrayCollection|object
   */
  public function findByUlnNumberAndCountryCode($ulnCountryCode, $ulnNumber)
  {
    return $this->findOneBy(array('ulnCountryCode'=>$ulnCountryCode, 'ulnNumber'=>$ulnNumber));
  }


  /**
   * @param $ulnCountryCode
   * @param $ulnNumber
   * @return Tag
   */
  public function findUnassignedTagByUlnNumberAndCountryCode($ulnCountryCode, $ulnNumber, $locationId = null)
  {
    return $this->findOneBy(array('ulnCountryCode'=>$ulnCountryCode,
                                  'ulnNumber'=>$ulnNumber,
                                  'tagStatus' => TagStateType::UNASSIGNED,
                                  'location' => $locationId));
  }


    /**
     * @param Client $client
     * @param Location $location
     * @param string $tagStatus
     * @param bool $ignoreLocationId
     * @param bool $checkIfCountryCodesMatchSelectedLocations
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
  public function findTags(Client $client, Location $location, $tagStatus = TagStateType::UNASSIGNED,
                           bool $ignoreLocationId = false, bool $checkIfCountryCodesMatchSelectedLocations = true)
  {
    if($client == null || $location == null || !is_int($location->getId())) { return []; }

    $locationFilter = $ignoreLocationId ? " " : " AND location_id = ".$location->getId()." ";
    $countryCodesCheckFilter = $checkIfCountryCodesMatchSelectedLocations ?
        " AND uln_country_code = (
          SELECT
            c.code as location_country_code
          FROM location l
            INNER JOIN address a ON a.id = l.address_id
            INNER JOIN country c ON c.name = a.country
          WHERE l.id = ".$location->getId()."
          LIMIT 1
        )  " : " ";

    $sql = "SELECT id, tag_status, animal_order_number, order_date, uln_country_code, uln_number
            FROM tag WHERE owner_id = ".$client->getId().$locationFilter.$countryCodesCheckFilter
            ."  AND tag_status = '".$tagStatus."'";
    $tags = $this->getManager()->getConnection()->query($sql)->fetchAll();

    return $tags;
  }

  /**
   * @param Client $client
   * @param int $count
   * @return Tag|ArrayCollection
   */
  public function findUnAssignedTags(Client $client, $count)
  {
    $tags = new ArrayCollection();

    $i = 0;

    foreach($client->getTags() as $tag){
      if($tag->getTagStatus() == TagStateType::UNASSIGNED) {
        $tags->add($tag);
        $i = $i + 1;
      }
      if($i = $count) { break; }
    }

    if(sizeof($tags)==1) {
      return $tags->get(0);
    } else {
      return $tags;
    }
  }

  /**
   * @param Client $client
   * @param string $ulnNumber
   * @param Location $location
   * @return bool
   */
  public function isAnUnassignedTag(Client $client, Location $location, $ulnNumber)
  {
    if($client == null || $location == null || $ulnNumber == null) { return false; }
    if(!is_int($client->getId()) || !is_int($location->getId()) ) { return false; }

    $tagStatus = TagStateType::UNASSIGNED;
    $sql = "SELECT COUNT(*)
            FROM tag WHERE owner_id = ".$client->getId()." AND location_id = ".$location->getId()."  AND tag_status = '".$tagStatus."'  AND uln_number = '".$ulnNumber."'";
    return $this->getManager()->getConnection()->query($sql)->fetch()['count'] > 0 ? true : false;
  }

  /**
   * @param Client $client
   * @param Ram|Ewe|Neuter $animal
   * @return Tag|null
   */
  public function findByAnimal($client, $animal)
  {
       return $this->findOneByUln($client, $animal->getUlnCountryCode(), $animal->getUlnNumber());
  }


  /**
   * @param Client $client
   * @param Location $location
   * @return int
   */
  public function getUnassignedTagCount(Client $client, Location $location) {
    if($client == null || $location == null) { return 0; }
    if(!is_int($client->getId()) || !is_int($location->getId()) ) { return false; }

    $tagStatus = TagStateType::UNASSIGNED;
    $sql = "SELECT COUNT(*)
            FROM tag WHERE owner_id = ".$client->getId()." AND location_id = ".$location->getId()."  AND tag_status = '".$tagStatus."'";
    return $this->getManager()->getConnection()->query($sql)->fetch()['count'];
  }


  /**
   * @param ObjectManager $manager
   * @param Location $location
   * @param Client $client
   * @param $ulnCountryCode
   * @param $ulnNumber
   * @param int $loopCount
   * @param int $maxRetries
   * @return \AppBundle\Component\HttpFoundation\JsonResponse|Tag
   * @throws \Doctrine\DBAL\DBALException
   */
  public function restoreTagWithPrimaryKeyCheck(ObjectManager $manager, Location $location, Client $client, $ulnCountryCode, $ulnNumber, $loopCount = 1, $maxRetries = 20)
  {
    try {
      $tagToRestore = new Tag();
      $tagToRestore->setLocation($location);
      $tagToRestore->setOrderDate(new \DateTime());
      $tagToRestore->setOwner($client);
      $tagToRestore->setTagStatus(TagStateType::UNASSIGNED);
      $tagToRestore->setUlnCountryCode($ulnCountryCode);
      $tagToRestore->setUlnNumber($ulnNumber);
      $tagToRestore->setAnimalOrderNumber(StringUtil::getLast5CharactersFromString($ulnNumber));
      $manager->persist($tagToRestore);
      $manager->flush();
      return $tagToRestore;

    } catch (UniqueConstraintViolationException $exception) {
      SqlUtil::bumpPrimaryKeySeq($this->getConnection(), 'tag');

      if($loopCount <= $maxRetries) {
        $this->restoreTagWithPrimaryKeyCheck($manager, $location, $client, $ulnCountryCode, $ulnNumber, $loopCount++, $maxRetries);
      }

      return Validator::createJsonResponse($exception, 428);
    }
  }


  /**
   * @param array $tags
   * @return bool
   */
  public function unassignTags(array $tags)
  {
    $tagIdsToUnassign = [];
    $incorrectTagCount = 0;
    foreach ($tags as $key => $tag) {
      if($tag instanceof Tag) {
        if($tag->getTagStatus() != TagStateType::UNASSIGNED && $tag->getId() != null) {
          $tagIdsToUnassign[] = $tag->getId();
          continue;
        }
      }
      $incorrectTagCount++;
    }
    if(count($tagIdsToUnassign) > 0) {
      return $this->unassignTagsById($tagIdsToUnassign);
    }
    return $incorrectTagCount == 0;
  }
  

  /**
   * @param array $tagIds
   * @return bool
   * @throws \Doctrine\DBAL\DBALException
   */
  public function unassignTagsById(array $tagIds)
  {
    if(count($tagIds) == 0) { return true; }

    $incorrectIdCount = 0;
    foreach ($tagIds as $key => $tagId) {
      if(!ctype_digit($tagId) && !is_int($tagId)) {
        $incorrectIdCount++;
        unset($tagIds[$key]);
        continue;
      }
    }

    $filterString = SqlUtil::getFilterStringByIdsArray($tagIds);
    if($filterString != '') {
      $sql = "UPDATE tag SET tag_status = '".TagStateType::UNASSIGNED."' WHERE ".$filterString;
      $this->getConnection()->exec($sql);
    }

    return $incorrectIdCount == 0;
  }


    /**
     * @param array $ulnParts
     * @return array|Tag[]
     */
  function findByUlnPartsArray(array $ulnParts = [])
  {
      if (empty($ulnParts)) {
          return [];
      }

      $qb = $this->getManager()->createQueryBuilder();

      $qb
          ->select('tag')
          ->from (Tag::class, 'tag')
      ;

      foreach ($ulnParts as $ulnPart) {
          $ulnCountryCode = $ulnPart[JsonInputConstant::ULN_COUNTRY_CODE];
          $ulnNumber = $ulnPart[JsonInputConstant::ULN_NUMBER];
          $qb->orWhere(
              $qb->expr()->andX(
                  $qb->expr()->eq('tag.ulnCountryCode', "'".$ulnCountryCode."'"),
                  $qb->expr()->eq('tag.ulnNumber', "'".$ulnNumber."'")
              )
          );
      }

      $query = $qb->getQuery();
      $query->useQueryCache(true);
      $query->setCacheable(true);

      $query->setFetchMode(Location::class, 'location', ClassMetadata::FETCH_EAGER);
      $query->setFetchMode(Client::class, 'owner', ClassMetadata::FETCH_EAGER);

      return $query->getResult();
  }


    /**
     * @param string $ulnCountryCode
     * @param string $ulnNumber
     * @return Tag|null
     */
    public function findByUln(string $ulnCountryCode, string $ulnNumber): ?Tag
    {
        return $this->findOneBy([
            'ulnCountryCode' => $ulnCountryCode,
            'ulnNumber' => $ulnNumber,
        ]);
    }


    /**
     * @param string $ulnCountryCode
     * @param string $ulnNumber
     * @return Tag|null
     */
  public function findReplacedTag($ulnCountryCode, $ulnNumber)
  {
      return $this->findTagByStatus($ulnCountryCode, $ulnNumber, TagStateType::REPLACED);
  }


    /**
     * @param string$ulnCountryCode
     * @param string $ulnNumber
     * @return null|Tag
     */
  public function findTagByStatus($ulnCountryCode, $ulnNumber, $status)
  {
      return $this->findOneBy([
         'ulnCountryCode' => $ulnCountryCode,
         'ulnNumber' => $ulnNumber,
         'tagStatus' => $status
      ]);
  }

}