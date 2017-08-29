<?php


namespace AppBundle\Traits;


use AppBundle\Util\StringUtil;

trait EntityClassInfo
{

    /**
     * @return string
     */
    static function getTableName()
    {
        return StringUtil::convertCamelCaseToSnakeCase(self::getShortClassName());
    }


    /**
     * @return string
     */
    static function getShortClassName() {
        return substr(strrchr(get_class(), '\\'), 1);
    }
}