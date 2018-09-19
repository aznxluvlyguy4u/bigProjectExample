<?php


namespace AppBundle\Util;


class StarValueUtil
{
    const STAR_ICON_EMPTY = 'EMPTY';
    const STAR_ICON_HALF = 'HALF';
    const STAR_ICON_FULL = 'FULL';

    const STAR_SCORE_5_MIN_INDEX = 5;
    const STAR_SCORE_4_AND_HALF_MIN_INDEX = 3;
    const STAR_SCORE_4_MIN_INDEX = 2;
    const STAR_SCORE_3_AND_HALF_MIN_INDEX = 1;
    const STAR_SCORE_3_MIN_INDEX = 0;
    const STAR_SCORE_2_AND_HALF_MIN_INDEX = -1;
    const STAR_SCORE_2_MIN_INDEX = -3;
    const STAR_SCORE_1_MIN_INDEX = -999999;

    /**
     * @param int $indexValue
     * @return float|int
     */
    public static function getStarValue($indexValue)
    {
        if($indexValue === null) {
            return 0;
        }

        if($indexValue > self::STAR_SCORE_5_MIN_INDEX) {
            return 5;

        } elseif($indexValue >= self::STAR_SCORE_4_AND_HALF_MIN_INDEX) {
            return 4.5;

        } elseif($indexValue >= self::STAR_SCORE_4_MIN_INDEX) {
            return 4;

        } elseif($indexValue >= self::STAR_SCORE_3_AND_HALF_MIN_INDEX) {
            return 3.5;

        } elseif($indexValue >= self::STAR_SCORE_3_MIN_INDEX) {
            return 3;

        } elseif($indexValue >= self::STAR_SCORE_2_AND_HALF_MIN_INDEX) {
            return 2.5;

        } elseif($indexValue >= self::STAR_SCORE_2_MIN_INDEX) {
            return 2;

        } else {
            return 1;
        }
    }


    /**
     * @param float $starsValue
     * @return array
     */
    public static function getStarsOutput($starsValue): array
    {
        return [
            self::singleOrHalfStartValue($starsValue, 0),
            self::singleOrHalfStartValue($starsValue, 1),
            self::singleOrHalfStartValue($starsValue, 2),
            self::singleOrHalfStartValue($starsValue, 3),
            self::singleOrHalfStartValue($starsValue, 4),
        ];
    }

    /**
     * @param float $totalStarsValue
     * @param float $baseValue
     * @return string
     */
    private static function singleOrHalfStartValue($totalStarsValue, $baseValue): string
    {
        $totalStarsValueTimesTen = intval($totalStarsValue * 10);
        $baseValueTimesTen = intval($baseValue * 10);
        $halfTimesTen = 5;

        $normalizedValueTimesTen = $totalStarsValueTimesTen - $baseValueTimesTen;

        if (empty($totalStarsValueTimesTen) || $totalStarsValueTimesTen <= $baseValueTimesTen) {
            return StarValueUtil::STAR_ICON_EMPTY;
        }

        if ($normalizedValueTimesTen > $halfTimesTen) {
            return StarValueUtil::STAR_ICON_FULL;
        }
        return StarValueUtil::STAR_ICON_HALF;
    }
}