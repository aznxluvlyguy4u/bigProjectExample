<?php

namespace AppBundle\Enumerator;

/**
 * Class InspectorMeasurementType
 * @package AppBundle\Enumerator
 */
class InspectorMeasurementType
{
    const PERFORMANCE = 'Performance';
    const EXTERIOR = 'Exterior';


    /**
     * @return array
     */
    public static function getTypes()
    {
        return [
            self::EXTERIOR => self::EXTERIOR,
            self::PERFORMANCE => self::PERFORMANCE,
        ];
    }
}