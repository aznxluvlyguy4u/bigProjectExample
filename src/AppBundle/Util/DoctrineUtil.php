<?php

namespace AppBundle\Util;


use AppBundle\Entity\Animal;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Tag;
use AppBundle\Entity\Token;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;

class DoctrineUtil
{

    /**
     * @param ObjectManager $om
     * @param $object
     * @return mixed
     */
    public static function persistAndFlush(ObjectManager $om, $object)
    {
        $om->persist($object);
        $om->flush();

        return $object;
    }

    /**
     * @param ObjectManager $om
     */
    public static function flushClearAndGarbageCollect(ObjectManager $om)
    {
        $om->flush();
        $om->clear();
        gc_collect_cycles();
    }


    /**
     * @param ObjectManager $em
     * @return string
     */
    public static function getDatabaseHostAndNameString(ObjectManager $em)
    {
        /** @var Connection $connection */
        $connection = $em->getConnection();
        $databaseName = $connection->getDatabase();
        $host = $connection->getHost();

        return 'Database: '.$databaseName.' on host '.$host;
    }


    /**
     * @param ObjectManager $em
     * @return string
     */
    public static function getRandomAdminAccessTokenCode(ObjectManager $em)
    {
        $sql = "SELECT token.code as code 
                FROM employee 
                    INNER JOIN token ON employee.id = token.owner_id
                    INNER JOIN person ON employee.id = person.id
                WHERE token.type = 'ACCESS' AND person.is_active = TRUE";
        $tokenCodes = $em->getConnection()->query($sql)->fetchAll();

        //null check
        if(count($tokenCodes) == 0) {
            return null;
        }

        $choice = rand(1, count($tokenCodes)-1);
        return $tokenCodes[$choice]['code'];
    }


    /**
     * @param ObjectManager $em
     * @param Location|int $locationToSkip
     * @param int $minAliveAnimalsCount
     * @return Location
     */
    public static function getRandomActiveLocation(ObjectManager $em, $locationToSkip = null, $minAliveAnimalsCount = 30)
    {
        if($locationToSkip instanceof Location) {
            $locationFilter = " AND location.id <> ".$locationToSkip->getId();
        } elseif (is_int($locationToSkip)) {
            $locationFilter = " AND location.id <> ".$locationToSkip;
        } else {
            $locationFilter = "";
        }

        $sql = "SELECT location.id as id 
                FROM (location 
                      INNER JOIN (
                                  SELECT location_id, COUNT(*) 
                                  FROM animal 
                                  WHERE animal.transfer_state IS NULL AND animal.is_alive = true 
                                  GROUP BY location_id HAVING COUNT(*) > ".$minAliveAnimalsCount." 
                                  ) 
                                  lc ON location.id = lc.location_id
                      ) 
                      WHERE location.is_active = TRUE".$locationFilter;

        $results = $em->getConnection()->query($sql)->fetchAll();
        return self::getRandomItemFromResults($em, $results, Location::class);
    }


    /**
     * @param ObjectManager $em
     * @param Location $location
     * @return null|Ram
     */
    public static function getRandomRamFromLocation(ObjectManager $em, Location $location)
    {
        return self::getRandomAnimalFromLocation($em, $location, 'Ram');
    }


    /**
     * @param ObjectManager $em
     * @param Location $location
     * @return null|Ewe
     */
    public static function getRandomEweFromLocation(ObjectManager $em, Location $location)
    {
        return self::getRandomAnimalFromLocation($em, $location, 'Ewe');
    }
    

    /**
     * @param ObjectManager $em
     * @param Location $location
     * @param null $gender
     * @return null|Animal|Ram|Ewe|Neuter
     */
    public static function getRandomAnimalFromLocation(ObjectManager $em, Location $location, $gender = null)
    {
        if($gender == 'Ram') {
            $typeFilter =  " AND a.type = 'Ram'";
            $clazz = Ram::class;
        } elseif ($gender == 'Ewe') {
            $typeFilter =  " AND a.type = 'Ewe'";
            $clazz = Ewe::class;
        } elseif ($gender == 'Neuter') {
            $typeFilter =  " AND a.type = 'Neuter'";
            $clazz = Neuter::class;
        } else {
            $typeFilter = null;
            $clazz = Animal::class;
        }

        $sql = "SELECT * FROM animal a WHERE a.location_id = ".$location->getId()." AND is_alive = TRUE AND a.transfer_state IS NULL ".$typeFilter;
        $results = $em->getConnection()->query($sql)->fetchAll();
        return self::getRandomItemFromResults($em, $results, $clazz);
    }


    /**
     * @param ObjectManager $em
     * @param Location $location
     * @return null|Tag
     */
    public static function getRandomUnassignedTag(ObjectManager $em, Location $location)
    {
        $ownerId = $location->getCompany()->getOwner()->getId();
        $locationId = $location->getId();
        $sql = "SELECT * FROM tag t WHERE t.owner_id = ".$ownerId." AND t.location_id = ".$locationId." AND tag_status = 'UNASSIGNED'";
        $results = $em->getConnection()->query($sql)->fetchAll();
        return self::getRandomItemFromResults($em, $results, Tag::class);
    }
    
    
    /**
     * @param ObjectManager $em
     * @param $results
     * @param $clazz
     * @return null|object
     */
    private static function getRandomItemFromResults(ObjectManager $em, $results, $clazz)
    {
        $resultsSize = count($results);
        //null check
        if($resultsSize == 0) {
            return null;
        }

        $result = null;
        $maximumRetries = 10;

        for($i = 0; $i < $maximumRetries; $i++) {
            $choice = rand(0, $resultsSize-1);
            $result = $em->getRepository($clazz)->find($results[$choice]['id']);

            if ($result != null) {
                return $result;
            }
        }
        return null;
    }
}