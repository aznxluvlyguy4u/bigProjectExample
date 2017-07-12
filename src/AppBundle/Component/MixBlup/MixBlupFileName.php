<?php


namespace AppBundle\Component\MixBlup;

use AppBundle\Enumerator\MixBlupType;
use AppBundle\Setting\MixBlupSetting;

/**
 * Class MixBlupFileName
 * @package AppBundle\MixBlup
 */
class MixBlupFileName
{
    /**
     * @param string $mixBlupType of MixBlupType enumerator
     * @return string
     */
    public static function getDataFileName($mixBlupType)
    {
        return MixBlupSetting::DATA_FILENAME_PREFIX.$mixBlupType.'.txt';
    }

    /**
     * @param string $mixBlupType of MixBlupType enumerator
     * @return string
     */
    public static function getPedigreeFileName($mixBlupType)
    {
        return MixBlupSetting::PEDIGREE_FILENAME_PREFIX.$mixBlupType.'.txt';
    }


    /** @return string */
    public static function getExteriorDataFileName() { return self::getDataFileName(MixBlupType::EXTERIOR); }
    /** @return string */
    public static function getLambMeatIndexDataFileName() { return self::getDataFileName(MixBlupType::LAMB_MEAT_INDEX); }
    /** @return string */
    public static function getFertilityDataFileName() { return self::getDataFileName(MixBlupType::FERTILITY); }

    /** @return string */
    public static function getExteriorPedigreeFileName() { return self::getPedigreeFileName(MixBlupType::EXTERIOR); }
    /** @return string */
    public static function getLambMeatIndexPedigreeFileName() { return self::getPedigreeFileName(MixBlupType::LAMB_MEAT_INDEX); }
    /** @return string */
    public static function getFertilityPedigreeFileName() { return self::getPedigreeFileName(MixBlupType::FERTILITY); }
}