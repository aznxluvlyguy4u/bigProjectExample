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
        $results = array();

        $results[] = AccessLevelType::ADMIN;
        $results[] = AccessLevelType::SUPER_ADMIN;
        $results[] = AccessLevelType::DEVELOPER;

        return $results;
    }

}