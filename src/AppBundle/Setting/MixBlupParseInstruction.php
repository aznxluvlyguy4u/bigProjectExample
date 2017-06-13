<?php


namespace AppBundle\Setting;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\MixBlupAnalysis;


/**
 * Class MixBlupParseInstruction
 * @package AppBundle\Setting
 */
class MixBlupParseInstruction
{

    /**
     * NOTE! At least
     *    JsonInputConstant::ANIMAL_ID
     * && JsonInputConstant::SOLANI_1
     * && JsonInputConstant::RELANI_1
     * must be included!
     *
     * @param string $breedValueType
     * @return array
     */
    public static function get($breedValueType)
    {
        switch ($breedValueType)
        {
            case MixBlupAnalysis::EXTERIOR_MUSCULARITY:
                return self::tripleSolaniAndRelaniColumn();

            case MixBlupAnalysis::FERTILITY_1:
                return self::singleSolaniAndRelaniColumn();

            case MixBlupAnalysis::FERTILITY_2:
                return self::singleSolaniAndRelaniColumn();

            case MixBlupAnalysis::FERTILITY_3:
                return self::singleSolaniAndRelaniColumn();

            case MixBlupAnalysis::LAMB_MEAT: //TODO
                return self::singleSolaniAndRelaniColumn();

            case MixBlupAnalysis::TAIL_LENGTH:
                return self::singleSolaniAndRelaniColumn();

            default:
                /*
                 * The default includes:
                 * InpGeboorte  MixBlupBreedValueType::BIRTH_PROGRESS
                 * InpExtOnt    MixBlupBreedValueType::EXTERIOR_PROGRESS
                 * ExtBeenw     MixBlupBreedValueType::EXTERIOR_LEG_WORK
                 * ExtEvenr     MixBlupBreedValueType::EXTERIOR_PROPORTION
                 * ExtKop       MixBlupBreedValueType::EXTERIOR_SKULL
                 * ExtType      MixBlupBreedValueType::EXTERIOR_TYPE
                 */
                return self::doubleSolaniAndRelaniColumn();
        }
    }


    /**
     * @return array
     */
    private static function singleSolaniAndRelaniColumn()
    {
        return [
            JsonInputConstant::ANIMAL_ID => 1,
            JsonInputConstant::SOLANI_1 => 4,
            //JsonInputConstant::RELANI_1 => 4, //TODO verify the relani
        ];
    }


    /**
     * @return array
     */
    private static function doubleSolaniAndRelaniColumn()
    {
        return [
            JsonInputConstant::ANIMAL_ID => 1,
            JsonInputConstant::SOLANI_1 => 4,
            JsonInputConstant::SOLANI_2 => 5, //TODO add relani
        ];
    }


    /**
     * @return array
     */
    private static function tripleSolaniAndRelaniColumn()
    {
        return [
            JsonInputConstant::ANIMAL_ID => 1,
            JsonInputConstant::SOLANI_1 => 4,
            JsonInputConstant::SOLANI_2 => 5,
            JsonInputConstant::SOLANI_3 => 6, //TODO add relani
        ];
    }
}