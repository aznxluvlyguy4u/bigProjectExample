<?php

namespace AppBundle\Output;

use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Province;

/**
 * Class ProvinceOutput
 * @package AppBundle\Output
 */
class ProvinceOutput extends Output
{

    /**
     * @param $provinces
     * @return array
     */
    public static function create($provinces, $includeName = false)
    {
        if($includeName) {
            $result = array(
                Constant::RESULT_NAMESPACE => self::createProvinceNameAndCodeArray($provinces)
            );

        } else {
            $result = array(
                JsonInputConstant::CODES => self::createProvinceCodeArray($provinces)
            );
        }
        return $result;
    }

    private static function createProvinceCodeArray($provinces)
    {
        $codes = array();

        foreach($provinces as $province) {
            $codes[] = $province->getCode();
        }

        return $codes;
    }


    private static function createProvinceNameAndCodeArray($provinces)
    {
        $codesAndNames = array();

        foreach($provinces as $province) {
            $codesAndNames[] = array(Constant::CODE_NAMESPACE => $province->getCode(),
                Constant::NAME_NAMESPACE => $province->getName());
        }

        return $codesAndNames;
    }

}