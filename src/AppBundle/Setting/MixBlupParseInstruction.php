<?php


namespace AppBundle\Setting;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\MixBlupAnalysis;
use AppBundle\MixBlup\ExteriorInstructionFiles;
use AppBundle\MixBlup\LambMeatIndexInstructionFiles;
use AppBundle\MixBlup\ReproductionInstructionFiles;


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
                $model = ExteriorInstructionFiles::getMuscularityModel(false);
                break;

            case MixBlupAnalysis::EXTERIOR_PROGRESS:
                $model = ExteriorInstructionFiles::getProgressModel(false);
                break;

            case MixBlupAnalysis::EXTERIOR_LEG_WORK:
                $model = ExteriorInstructionFiles::getLegWorkModel(false);
                break;

            case MixBlupAnalysis::EXTERIOR_PROPORTION:
                $model = ExteriorInstructionFiles::getProportionModel(false);
                break;

            case MixBlupAnalysis::EXTERIOR_SKULL:
                $model = ExteriorInstructionFiles::getSkullModel(false);
                break;

            case MixBlupAnalysis::EXTERIOR_TYPE:
                $model = ExteriorInstructionFiles::getExteriorTypeModel(false);
                break;


            case MixBlupAnalysis::BIRTH_PROGRESS:
                $model = ReproductionInstructionFiles::getBirthProgressModel(false);
                break;

            case MixBlupAnalysis::FERTILITY_1:
                $model = ReproductionInstructionFiles::getFertilityModel(1,false);
                break;

            case MixBlupAnalysis::FERTILITY_2:
                $model = ReproductionInstructionFiles::getFertilityModel(2,false);
                break;

            case MixBlupAnalysis::FERTILITY_3:
                $model = ReproductionInstructionFiles::getFertilityModel(3,false);
                break;


            case MixBlupAnalysis::LAMB_MEAT:
                $model = LambMeatIndexInstructionFiles::getLambMeatModel(false);
                break;

            case MixBlupAnalysis::TAIL_LENGTH:
                $model = LambMeatIndexInstructionFiles::getTailLengthModel(false);
                break;

            default:
                $model = [];
        }
        return array_keys($model);

    }


}