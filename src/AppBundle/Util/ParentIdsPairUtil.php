<?php


namespace AppBundle\Util;


use AppBundle\model\ParentIdsPair;

class ParentIdsPairUtil
{
    const EWE_ID = 'ewe_id';
    const RAM_ID = 'ram_id';

    /**
     * All animals in collection should have both a mother and father
     *
     * @param array $result
     * @return array|ParentIdsPair[]
     */
    public static function fromSqlResult(array $result): array {
        $parentIdsPairs = array_map(function(array $result) {
            return new ParentIdsPair(
                $result[self::RAM_ID],
                $result[self::EWE_ID]
            );
        }, $result);
        return self::uniqueParentIdsPairs($parentIdsPairs);
    }

    /**
     * @param array|ParentIdsPair[] $parentIdsPairs
     * @return array
     */
    private static function uniqueParentIdsPairs(array $parentIdsPairs): array {
        $uniqueResults = [];
        foreach ($parentIdsPairs as $parentIdsPair) {
            $key = $parentIdsPair->getRamId().'-'.$parentIdsPair->getEweId();
            if (key_exists($key, $parentIdsPair)) {
                continue;
            }
            $uniqueResults[$key] = $parentIdsPair;
        }
        return $uniqueResults;
    }
}