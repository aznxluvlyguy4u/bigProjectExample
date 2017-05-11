<?php


namespace AppBundle\Enumerator;


class MixBlupNullFiller
{
    //Booleans
    const PMSG = -99;
    const PRECOCIOUS = -99; //vroegrijp

    //Date
    const DATE = 'N_B';

    //Floats
    const GROWTH = -99;
    const HETEROSIS = -99;
    const MEASUREMENT_VALUE = -99;
    const RECOMBINATION = -99;

    //Integers
    const AGE = -99;
    const BIRTH_PROGRESS = -99;
    const BLOCK = 3;
    const COUNT = -99;
    const UBN = 0; //Value is used as default value for !BLOCK

    //Labels/Strings
    const CODE = 'N_B';
    const GENDER = 'N_B'; //Including Neuter!
    const GROUP = 'N_B';
    const ULN = 0;
    const TYPE = 'N_B';
}