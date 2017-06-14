<?php


namespace AppBundle\Constant;

/**
 * Class BreedValueType
 * @package AppBundle\Constant
 */
class BreedValueTypeConstant
{
    //LambMeatIndex
    const BIRTH_WEIGHT = 'GewGeb';
    const WEIGHT_AT_8_WEEKS = 'Gew08';
    const WEIGHT_AT_20_WEEKS = 'Gew20';
    const FAT_THICKNESS_1 = 'Vetd01';
    const FAT_THICKNESS_2 = 'Vetd02';
    const FAT_THICKNESS_3 = 'Vetd03';
    const MUSCLE_THICKNESS = 'Spierd';

    //TailLengthIndex
    const TAIL_LENGTH = 'StaartLen';

    //FertilityIndex
    const BIRTH_PROGRESS = 'GebGemak';
    const TOTAL_BORN = 'TotGeb';
    const STILL_BORN = 'DoodGeb';
    const EARLY_FERTILITY = 'Vroeg';
    const BIRTH_INTERVAL = 'TusLamT';

    //ExteriorIndex, NOTE linear breed values are not included at the moment
    const LEG_WORK_VG_M = 'BeenwVGm';
    const LEG_WORK_DF = 'BeenwDF';
    const MUSCULARITY_VG_V = 'BespVGv';
    const MUSCULARITY_VG_M = 'BespVGm';
    const MUSCULARITY_DF = 'BespDF';
    const PROPORTION_VG_M = 'EvenrVGm';
    const PROPORTION_DF = 'EvenrDF';
    const SKULL_VG_M = 'KopVGm';
    const SKULL_DF = 'KopDF';
    const PROGRESS_VG_M = 'OntwVGm';
    const PROGRESS_DF = 'OntwDF';
    const EXTERIOR_TYPE_VG_M = 'TypeVGm';
    const EXTERIOR_TYPE_DF = 'TypeDF';


    /**
     * @return array
     */
    static function getConstants() {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
}