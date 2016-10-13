<?php


namespace AppBundle\Util;


use AppBundle\Constant\ReportFormat;

class StarValueUtil
{
    const STAR_SCORE_5_MIN_LAMB_MEAT_INDEX = 105;
    const STAR_SCORE_4_AND_HALF_MIN_LAMB_MEAT_INDEX = 103;
    const STAR_SCORE_4_MIN_LAMB_MEAT_INDEX = 70;
    const STAR_SCORE_3_AND_HALF_MIN_LAMB_MEAT_INDEX = 60;
    const STAR_SCORE_3_MIN_LAMB_MEAT_INDEX = 50;
    const STAR_SCORE_2_AND_HALF_MIN_LAMB_MEAT_INDEX = 40;
    const STAR_SCORE_2_MIN_LAMB_MEAT_INDEX = 20;
    const STAR_SCORE_1_MIN_LAMB_MEAT_INDEX = 0;

    /**
     * @param int $indexRank
     * @param int $totalIndexRankedAnimals
     * @return float|int
     */
    public static function getStarValue($indexRank, $totalIndexRankedAnimals)
    {
        if(NullChecker::numberIsNull($indexRank) || NullChecker::numberIsNull($totalIndexRankedAnimals)) {
            return 0;
        }

        $rankPercentage = floor((floatval($totalIndexRankedAnimals) - floatval($indexRank))/floatval($totalIndexRankedAnimals) * 100);

        if($rankPercentage >= ReportFormat::STAR_SCORE_5_MIN_PERCENTAGE) {
            return 5;

        } elseif($rankPercentage >= ReportFormat::STAR_SCORE_4_AND_HALF_MIN_PERCENTAGE) {
            return 4.5;

        } elseif($rankPercentage >= ReportFormat::STAR_SCORE_4_MIN_PERCENTAGE) {
            return 4;

        } elseif($rankPercentage >= ReportFormat::STAR_SCORE_3_AND_HALF_MIN_PERCENTAGE) {
            return 3.5;

        } elseif($rankPercentage >= ReportFormat::STAR_SCORE_3_MIN_PERCENTAGE) {
            return 3;

        } elseif($rankPercentage >= ReportFormat::STAR_SCORE_2_AND_HALF_MIN_PERCENTAGE) {
            return 2.5;

        } elseif($rankPercentage >= ReportFormat::STAR_SCORE_2_MIN_PERCENTAGE) {
            return 2;

        } elseif($rankPercentage >= ReportFormat::STAR_SCORE_1_MIN_PERCENTAGE) {
            return 1;
        }
    }
}