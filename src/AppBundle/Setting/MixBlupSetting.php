<?php


namespace AppBundle\Setting;

/**
 * Class MixBlupSetting
 * @package AppBundle\Setting
 */
class MixBlupSetting
{
    const PARFILE_FILENAME = 'ParNSFO.txt';

    const PEDIGREE_FILENAME_PREFIX = 'Ped';
    const DATA_FILENAME_PREFIX = 'Data';

    const DECIMAL_SEPARATOR = '.';
    const THOUSANDS_SEPARATOR = '';
    
    const INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS = false;

    const MEASUREMENTS_FROM_LAST_AMOUNT_OF_YEARS = 15;
    const COLUMN_SPACING = 1;

    //Rounding Accuracies
    const HETEROSIS_AND_RECOMBINATION_ROUNDING_ACCURACY = 2;
}