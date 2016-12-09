<?php


namespace AppBundle\Util;


class MathUtil
{
    /**
     * @param array $values
     * @param float $mean
     * @return float
     */
    public static function standardDeviation($values, $mean)
    {
        $sumOfDifferences = 0;
        foreach ($values as $value) {
            $sumOfDifferences += pow(($value - $mean), 2);
        }
        $variance = $sumOfDifferences / count($values);

        return sqrt($variance);
    }
}