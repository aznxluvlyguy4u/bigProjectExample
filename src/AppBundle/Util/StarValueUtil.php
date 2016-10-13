<?php


namespace AppBundle\Util;


class StarValueUtil
{
    const STAR_SCORE_5_MIN_LAMB_MEAT_INDEX = 5;
    const STAR_SCORE_4_AND_HALF_MIN_LAMB_MEAT_INDEX = 3;
    const STAR_SCORE_4_MIN_LAMB_MEAT_INDEX = 2;
    const STAR_SCORE_3_AND_HALF_MIN_LAMB_MEAT_INDEX = 1;
    const STAR_SCORE_3_MIN_LAMB_MEAT_INDEX = 0;
    const STAR_SCORE_2_AND_HALF_MIN_LAMB_MEAT_INDEX = -1;
    const STAR_SCORE_2_MIN_LAMB_MEAT_INDEX = -3;
    const STAR_SCORE_1_MIN_LAMB_MEAT_INDEX = 0;

    /**
     * @param int $indexValue
     * @param int $meanIndexValue
     * @return float|int
     */
    public static function getStarValue($indexValue, $meanIndexValue)
    {
        if($indexValue === null || $meanIndexValue === null) {
            return 0;
        }

        if($indexValue > self::STAR_SCORE_5_MIN_LAMB_MEAT_INDEX + $meanIndexValue) {
            return 5;

        } elseif($indexValue >= self::STAR_SCORE_4_AND_HALF_MIN_LAMB_MEAT_INDEX + $meanIndexValue) {
            return 4.5;

        } elseif($indexValue >= self::STAR_SCORE_4_MIN_LAMB_MEAT_INDEX + $meanIndexValue) {
            return 4;

        } elseif($indexValue >= self::STAR_SCORE_3_AND_HALF_MIN_LAMB_MEAT_INDEX + $meanIndexValue) {
            return 3.5;

        } elseif($indexValue >= self::STAR_SCORE_3_MIN_LAMB_MEAT_INDEX + $meanIndexValue) {
            return 3;

        } elseif($indexValue >= self::STAR_SCORE_2_AND_HALF_MIN_LAMB_MEAT_INDEX + $meanIndexValue) {
            return 2.5;

        } elseif($indexValue >= self::STAR_SCORE_2_MIN_LAMB_MEAT_INDEX + $meanIndexValue) {
            return 2;

        } elseif($indexValue >= self::STAR_SCORE_1_MIN_LAMB_MEAT_INDEX + $meanIndexValue) {
            return 1;
        }
    }
}