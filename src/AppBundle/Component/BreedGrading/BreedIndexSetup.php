<?php


namespace AppBundle\Component\BreedGrading;


use AppBundle\Component\MixBlup\ExteriorInstructionFiles;
use AppBundle\Component\MixBlup\LambMeatIndexInstructionFiles;
use AppBundle\Component\MixBlup\ReproductionInstructionFiles;
use AppBundle\Constant\BreedIndexTypeConstant;
use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Util\ArrayUtil;


/**
 * Class BreedIndexSetup
 * @package AppBundle\Component\BreedGrading
 */
class BreedIndexSetup
{
    /**
     * TODO This will be implemented in the future and will be similar to the lambMeatIndex with some extra breedValues
     *
     * @return array
     * @throws \Exception
     */
    public static function fatherIndex()
    {
        return [];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public static function lambMeatIndex()
    {
        $includedBreedValues = [
            BreedValueTypeConstant::WEIGHT_AT_20_WEEKS,
            BreedValueTypeConstant::FAT_THICKNESS_3,
            BreedValueTypeConstant::MUSCLE_THICKNESS,
        ];

        $breedValuesInMixBlupFiles = LambMeatIndexInstructionFiles::getLambMeatModel(false);

        self::validateIncludedBreedValues($includedBreedValues, $breedValuesInMixBlupFiles,
            BreedIndexTypeConstant::LAMB_MEAT_INDEX);

        return $includedBreedValues;
    }


    /**
     * @return array
     * @throws \Exception
     */
    public static function motherIndex()
    {
        return self::fertilityIndex();
    }


    /**
     * @return array
     * @throws \Exception
     */
    public static function fertilityIndex()
    {
        $includedBreedValues = [
            BreedValueTypeConstant::TOTAL_BORN,
            BreedValueTypeConstant::STILL_BORN,
            BreedValueTypeConstant::EARLY_FERTILITY,
        ];

        $breedValuesInMixBlupFiles = ArrayUtil::concatArrayValues([
            ReproductionInstructionFiles::getFertilityModel(1,false),
            ReproductionInstructionFiles::getFertilityModel(2,false),
            ReproductionInstructionFiles::getFertilityModel(3,false),
        ], false);

        self::validateIncludedBreedValues($includedBreedValues, $breedValuesInMixBlupFiles,
            BreedIndexTypeConstant::FERTILITY_INDEX);

        return $includedBreedValues;
    }


    /**
     * @return array
     * @throws \Exception
     */
    public static function exteriorIndex()
    {
        $includedBreedValues = [
            BreedValueTypeConstant::EXTERIOR_TYPE_DF,
            BreedValueTypeConstant::LEG_WORK_DF,
            BreedValueTypeConstant::MUSCULARITY_DF,
            BreedValueTypeConstant::PROGRESS_DF,
            BreedValueTypeConstant::PROPORTION_DF,
            BreedValueTypeConstant::SKULL_DF,
        ];

        $breedValuesInMixBlupFiles = ArrayUtil::concatArrayValues([
            ExteriorInstructionFiles::getLegWorkModel(false),
            ExteriorInstructionFiles::getProgressModel(false),
            ExteriorInstructionFiles::getProportionModel(false),
            ExteriorInstructionFiles::getMuscularityModel(false),
            ExteriorInstructionFiles::getSkullModel(false),
            ExteriorInstructionFiles::getExteriorTypeModel(false),
        ], false);

        self::validateIncludedBreedValues($includedBreedValues, $breedValuesInMixBlupFiles,
            BreedIndexTypeConstant::EXTERIOR_INDEX);

        return $includedBreedValues;
    }


    /**
     * @param array $includedBreedValues
     * @param array $breedValuesInMixBlupFiles
     * @param string $indexName
     * @throws \Exception
     */
    private static function validateIncludedBreedValues($includedBreedValues, $breedValuesInMixBlupFiles, $indexName)
    {
        $missingBreedValues = [];

        foreach ($includedBreedValues as $includedBreedValue) {
            if(!key_exists($includedBreedValue, $breedValuesInMixBlupFiles)) {
                $missingBreedValues[] = $includedBreedValue;
            }
        }

        if(count($missingBreedValues) > 0) {
            throw new \Exception($indexName.' includes the following breedValues not found in MixBlup files: '
                .implode(', ', $missingBreedValues), 500);
        }
    }
}