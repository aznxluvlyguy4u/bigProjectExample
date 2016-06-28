<?php

namespace AppBundle\Output;

use AppBundle\Constant\Constant;

/**
 * Class ProcessorOutput
 */
class ProcessorOutput extends Output
{

    /**
     * @param $processors
     * @return array
     */
    public static function create($processors, $includeName = false)
    {
        if($includeName) {
            $result = array(
                Constant::RESULT_NAMESPACE => self::createUbnsAndNameArray($processors)
            );

        } else {
            $result = array(
                Constant::RESULT_NAMESPACE => self::createUbnsArray($processors)
            );
        }
        return $result;
    }


    private static function createUbnsArray($processors)
    {
        $ubns = array();

        foreach($processors as $processor) {
            $ubns[] = $processor->getUbn();
        }

        return $ubns;
    }

    private static function createUbnsAndNameArray($processors)
    {
        $ubnsAndNames = array();

        foreach($processors as $processor) {
            $ubnsAndNames[] = array(Constant::UBN_NAMESPACE => $processor->getUbn(),
                Constant::NAME_NAMESPACE => $processor->getName());
        }

        return $ubnsAndNames;
    }

}