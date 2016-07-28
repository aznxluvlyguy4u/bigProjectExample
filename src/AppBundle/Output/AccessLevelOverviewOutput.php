<?php

namespace AppBundle\Output;

use AppBundle\Enumerator\AccessLevelType;

class AccessLevelOverviewOutput
{
    /**
     * @return array
     */
    public static function create()
    {
        return self::getTypes(); //for now the output is identical to an array of the types
    }

    /**
     * @return array
     */
    public static function getTypes()
    {
        $results = array();

        $results[] = AccessLevelType::ADMIN;
        $results[] = AccessLevelType::SUPER_ADMIN;
        $results[] = AccessLevelType::DEVELOPER;

        return $results;
    }

}