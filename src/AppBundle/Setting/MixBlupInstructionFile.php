<?php


namespace AppBundle\Setting;

/**
 * Class MixBlupInstructionFile
 * @package AppBundle\Setting
 */
class MixBlupInstructionFile
{
    //NOTE! Instruction filenames should start with 'Inp' to make sure the mixblup worker properly parses the zipFileName
    
    //Exterior
    const EXTERIOR_LEG_WORK = 'InpExtBeenw.txt';
    const EXTERIOR_MUSCULARITY = 'InpExtBesp.txt';
    const EXTERIOR_PROPORTION = 'InpExtEvenr.txt';
    const EXTERIOR_SKULL = 'InpExtKop.txt';
    const EXTERIOR_PROGRESS = 'InpExtOntw.txt';
    const EXTERIOR_TYPE = 'InpExtType.txt';

    //Lamb Meat
    const LAMB_MEAT = 'InpVleeslam.txt';
    const TAIL_LENGTH = 'InpStaartLen.txt';

    //Reproduction
    const BIRTH_PROGRESS = 'InpGeboorte.txt';
    const FERTILITY = 'InpVruchtb.txt';
    const FERTILITY_1 = 'InpVruchtb1.txt';
    const FERTILITY_2 = 'InpVruchtb2.txt';
    const FERTILITY_3 = 'InpVruchtb3.txt';
    const FERTILITY_4 = 'InpVruchtb4.txt';

    //Worm Resistance
    const WORM_RESISTANCE = 'InpWormRes.txt';


    /**
     * @param $instructionFilename
     * @return string
     */
    public static function relani($instructionFilename)
    {
        return rtrim($instructionFilename, '.txt') . MixBlupSetting::RELANI_SUFFIX . '.txt';
    }
}