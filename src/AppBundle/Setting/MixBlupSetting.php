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
    const RELANI_SUFFIX = 'Relani';

    const DECIMAL_SEPARATOR = '.';
    const THOUSANDS_SEPARATOR = '';
    const FLOAT_ACCURACY = 0.00000000001;

    const INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS = false;

    const MEASUREMENTS_FROM_LAST_AMOUNT_OF_YEARS = 15;
    const COLUMN_SPACING = 1;

    const INCLUDE_ULNS = false;

    //Rounding Accuracies
    const HETEROSIS_AND_RECOMBINATION_ROUNDING_ACCURACY = 2;
    
    //S3 Bucket
    const S3_MIXBLUP_INPUT_DIRECTORY = 'mixblub_input_files/';
    const S3_MIXBLUP_OUTPUT_DIRECTORY = 'mixblub_output_files/';

    const FALSE_RECORD_VALUE = 0;
    const TRUE_RECORD_VALUE = 1;

    //Filtering out animals who are their own ascendants
    const FILTER_OUT_ANIMALS_WHO_ARE_THEIR_OWN_ASCENDANTS = true;
    const FILTER_OUT_FROM_PEDIDGREE_FILE_DAYS_DIFFERENCE_BETWEEN_CHILD_AND_PARENT = 0;
}