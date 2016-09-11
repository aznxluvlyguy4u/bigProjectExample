<?php

namespace AppBundle\Util;


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
}