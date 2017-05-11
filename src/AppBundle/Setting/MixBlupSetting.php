<?php


namespace AppBundle\Setting;


class MixBlupSetting
{
    const WORKING_FOLDER_NAME = 'mixblup';

    const OUTPUT_FOLDER_PATH = '/Resources/outputs/mixblup';
    const MUTATIONS_FOLDER_PATH = '/Resources/mutations';

    const PARFILE_FILENAME = 'ParNSFO.txt';
    const PEDIGREE_FILENAME = 'pedigree';
    const INSTRUCTIONS_FILENAME = 'instruction';

    const PEDIGREE_FILENAME_PREFIX = 'Ped';
    const DATA_FILENAME_PREFIX = 'Data';
    const LAMB_MEAT_INDEX = 'Vleeslam';
    const FERTILITY = 'Vruchtb';
    const WORM = 'Worm';
    const EXTERIOR = 'Exterieur';

    const DECIMAL_SEPARATOR = '.';
    const THOUSANDS_SEPARATOR = '';
    
    const INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS = false;
    
    //Reproduction
    const LITTER_SIZE_INSTRUCTION_FILE = 'Worpgrootte';
    const BIRTH_PROCESS_INSTRUCTION_FILE = 'Geboorteverloop';
    const GAVE_BIRTH_AS_ONE_YEAR_OLD_INSTRUCTION_FILE = 'Vroegrijpheid';
}