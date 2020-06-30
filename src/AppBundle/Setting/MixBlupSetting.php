<?php


namespace AppBundle\Setting;

use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Constant\MixBlupAnalysis;
use AppBundle\Exception\MiXBLUP\MixBlupException;

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

    /**
     * @param MixBlupAnalysis|string $mixblupAnalysis
     * @return array
     * @throws MixBlupException
     */
    static function breedTypeByAnalysis($mixblupAnalysis): array {
        $keys = [
            // Lamb Meat
            MixBlupAnalysis::LAMB_MEAT => [
                BreedValueTypeConstant::FAT_THICKNESS_1,
                BreedValueTypeConstant::FAT_THICKNESS_2,
                BreedValueTypeConstant::FAT_THICKNESS_3,
                BreedValueTypeConstant::MUSCLE_THICKNESS,
                BreedValueTypeConstant::BIRTH_WEIGHT,
                BreedValueTypeConstant::WEIGHT_AT_8_WEEKS,
                BreedValueTypeConstant::WEIGHT_AT_20_WEEKS,
                BreedValueTypeConstant::GROWTH,
                BreedValueTypeConstant::TAIL_LENGTH,
            ],
            MixBlupAnalysis::TAIL_LENGTH => [
                BreedValueTypeConstant::TAIL_LENGTH
            ],
            // Exterior
            MixBlupAnalysis::EXTERIOR_LEG_WORK => [
                BreedValueTypeConstant::LEG_WORK_DF,
                BreedValueTypeConstant::LEG_WORK_VG_M,
            ],
            MixBlupAnalysis::EXTERIOR_MUSCULARITY => [
                BreedValueTypeConstant::MUSCULARITY_DF,
                BreedValueTypeConstant::MUSCULARITY_VG_M,
                BreedValueTypeConstant::MUSCULARITY_VG_V,
            ],
            MixBlupAnalysis::EXTERIOR_PROGRESS => [
                BreedValueTypeConstant::PROGRESS_DF,
                BreedValueTypeConstant::PROGRESS_VG_M,
            ],
            MixBlupAnalysis::EXTERIOR_PROPORTION => [
                BreedValueTypeConstant::PROPORTION_DF,
                BreedValueTypeConstant::PROPORTION_VG_M,
            ],
            MixBlupAnalysis::EXTERIOR_SKULL => [
                BreedValueTypeConstant::SKULL_DF,
                BreedValueTypeConstant::SKULL_VG_M,
            ],
            MixBlupAnalysis::EXTERIOR_TYPE => [
                BreedValueTypeConstant::EXTERIOR_TYPE_DF,
                BreedValueTypeConstant::EXTERIOR_TYPE_VG_M,
            ],
            // Fertility
            MixBlupAnalysis::FERTILITY => [
                BreedValueTypeConstant::BIRTH_PROGRESS,
                BreedValueTypeConstant::BIRTH_DELIVERY_PROGRESS,
                BreedValueTypeConstant::TOTAL_BORN,
                BreedValueTypeConstant::STILL_BORN,
                BreedValueTypeConstant::EARLY_FERTILITY,
                BreedValueTypeConstant::BIRTH_INTERVAL,
            ],
            MixBlupAnalysis::BIRTH_PROGRESS => [
                BreedValueTypeConstant::BIRTH_PROGRESS,
                BreedValueTypeConstant::BIRTH_DELIVERY_PROGRESS,
            ],
            MixBlupAnalysis::FERTILITY_1 => [
                BreedValueTypeConstant::TOTAL_BORN,
            ],
            MixBlupAnalysis::FERTILITY_2 => [
                BreedValueTypeConstant::STILL_BORN,
            ],
            MixBlupAnalysis::FERTILITY_3 => [
                BreedValueTypeConstant::EARLY_FERTILITY,
            ],
            MixBlupAnalysis::FERTILITY_4 => [
                BreedValueTypeConstant::BIRTH_INTERVAL,
            ],
            // WormResistance
            MixBlupAnalysis::WORM_RESISTANCE => [
                BreedValueTypeConstant::NATURAL_LOGARITHM_EGG_COUNT,
                BreedValueTypeConstant::IGA_NEW_ZEALAND,
                BreedValueTypeConstant::IGA_SCOTLAND,
                BreedValueTypeConstant::ODIN_BC,
            ],
        ];

        foreach (MixBlupAnalysis::getConstants() as $analysisType) {
            if (!key_exists($analysisType, $keys)) {
                throw new MixBlupException($analysisType . ' is missing from breedTypeByAnalysis set');
            }
        }

        return $keys[$mixblupAnalysis];
    }
}