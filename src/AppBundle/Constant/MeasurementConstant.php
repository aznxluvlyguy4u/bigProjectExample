<?php

namespace AppBundle\Constant;


class MeasurementConstant
{
    const DATE = 'date';
    const ONE = 'one';
    const TWO = 'two';
    const THREE = 'three';
    const ANIMAL_ID = 'animal_id';

    /* Filter values */

    //Age is in days
    const BIRTH_WEIGHT_MIN_AGE = 0;
    const BIRTH_WEIGHT_MAX_AGE = 3;
    const WEIGHT_AT_8_WEEKS_MIN_AGE = 42;
    const WEIGHT_AT_8_WEEKS_MAX_AGE = 70;
    const WEIGHT_AT_20_WEEKS_MIN_AGE = 98;
    const WEIGHT_AT_20_WEEKS_MAX_AGE = 168;

    //Weights in kg
    const BIRTH_WEIGHT_MIN_VALUE = 0.5;
    const BIRTH_WEIGHT_MAX_VALUE = 10.0;
    const WEIGHT_AT_8_WEEKS_MIN_VALUE = 8.0;
    const WEIGHT_AT_8_WEEKS_MAX_VALUE = 42.0;
    const WEIGHT_AT_20_WEEKS_MIN_VALUE = 15.0;
    const WEIGHT_AT_20_WEEKS_MAX_VALUE = 75.0;
}