<?php

namespace AppBundle\Constant;


class MeasurementConstant
{
    const DATE = 'date';
    const ONE = 'one';
    const TWO = 'two';
    const THREE = 'three';
    const ANIMAL_ID = 'animal_id';


    /* Mixblup filter values */

    //Age is in days
    const BIRTH_WEIGHT_MIN_AGE = 0;
    const BIRTH_WEIGHT_MAX_AGE = 3;
    const WEIGHT_AT_8_WEEKS_MIN_AGE = 42;
    const WEIGHT_AT_8_WEEKS_MEDIAN_AGE = 56;
    const WEIGHT_AT_8_WEEKS_MAX_AGE = 70;
    const WEIGHT_AT_20_WEEKS_MIN_AGE = 98;
    const WEIGHT_AT_20_WEEKS_MEDIAN_AGE = 140;
    const WEIGHT_AT_20_WEEKS_MAX_AGE = 168;

    //Weights in kg
    const BIRTH_WEIGHT_MIN_VALUE = 0.5;
    const BIRTH_WEIGHT_MAX_VALUE = 10.0;
    const WEIGHT_AT_8_WEEKS_MIN_VALUE = 8.0;
    const WEIGHT_AT_8_WEEKS_MAX_VALUE = 42.0;
    const WEIGHT_AT_20_WEEKS_MIN_VALUE = 15.0;
    const WEIGHT_AT_20_WEEKS_MAX_VALUE = 75.0;

    const FAT_MIN_VALUE = 1.0;
    const FAT_MAX_VALUE = 6.0;
    const MUSCLE_THICKNESS_MIN_VALUE = 15.0;
    const MUSCLE_THICKNESS_MAX_VALUE = 39.0;

    const EXTERIOR_MAX_VALUE_LIMIT = 100.0;

    const TAIL_LENGTH_MAX = 27;
    const TAIL_LENGTH_MIN = 8;

    const N_LING_MAX = 6;
    const N_LING_MIN = 1;

    /* End Mixblup values */
}