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
}