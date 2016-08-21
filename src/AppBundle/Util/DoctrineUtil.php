<?php

namespace AppBundle\Util;


use Doctrine\Common\Persistence\ObjectManager;

class DoctrineUtil
{

    /**
     * @param ObjectManager $om
     * @param $object
     */
    public static function persistPlus(ObjectManager $om, $object)
    {
        $om->persist($object);
        $om->flush();
        $om->clear();
        gc_collect_cycles();
    }

    /**
     * @param ObjectManager $om
     */
    public static function flushPlus(ObjectManager $om)
    {
        $om->flush();
        $om->clear();
        gc_collect_cycles();
    }
}