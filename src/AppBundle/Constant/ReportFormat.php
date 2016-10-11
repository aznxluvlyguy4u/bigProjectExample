<?php


namespace AppBundle\Constant;


class ReportFormat
{
    const DECIMAL_CHAR = ',';
    const THOUSANDS_SEP_CHAR = '.';

    //Units
    const GROWTH_DISPLAY_FACTOR = 1000;         //1000 = mg/day , 1 = kg/day
    const FAT_DISPLAY_FACTOR = 1;               //1 = mm
    const MUSCLE_THICKNESS_DISPLAY_FACTOR = 1;  //1 = mm
    
    const STAR_SCORE_5_MIN_PERCENTAGE = 90;
    const STAR_SCORE_4_AND_HALF_MIN_PERCENTAGE = 80;
    const STAR_SCORE_4_MIN_PERCENTAGE = 70;
    const STAR_SCORE_3_AND_HALF_MIN_PERCENTAGE = 60;
    const STAR_SCORE_3_MIN_PERCENTAGE = 50;
    const STAR_SCORE_2_AND_HALF_MIN_PERCENTAGE = 40;
    const STAR_SCORE_2_MIN_PERCENTAGE = 20;
    const STAR_SCORE_1_MIN_PERCENTAGE = 0;
}