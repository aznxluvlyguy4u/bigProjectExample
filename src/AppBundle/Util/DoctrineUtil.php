<?php

namespace AppBundle\Util;


use AppBundle\Entity\Employee;
use AppBundle\Entity\Location;
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
     * @param int $minAliveAnimalsCount
     * @return Location
     */
    public static function getRandomActiveLocation(ObjectManager $em, $minAliveAnimalsCount = 30)
    {

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
                      WHERE location.is_active = TRUE";

        $results = $em->getConnection()->query($sql)->fetchAll();

        //null check
        if(count($results) == 0) {
            return null;
        }

        $choice = rand(1, count($results)-1);
        return $em->getRepository(Location::class)->find($results[$choice]['id']);
    }
}