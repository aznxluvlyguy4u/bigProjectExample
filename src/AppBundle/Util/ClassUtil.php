<?php


namespace AppBundle\Util;


use ReflectionClass;

class ClassUtil
{
    public static function getShortName(object $object): string
    {
        return (new ReflectionClass($object))->getShortName();
    }
}
