<?php


namespace AppBundle\Util;


use AppBundle\Constant\Environment;
use AppBundle\Enumerator\AnimalEnvType;
use AppBundle\Exception\Env\EnvException;

class EnvUtil
{
    public static function validateAnimalTypeEnv(string $animalEnv)
    {
        self::validateBase($animalEnv, 'animal_type_env', AnimalEnvType::getConstants());
    }


    public static function validateEnvironment(string $env)
    {
        self::validateBase($env, 'animal_type_env', Environment::getConstants());
    }


    private static function validateBase($input, $key, $options)
    {
        if (!in_array($input, $options)) {
            throw new EnvException($input, $key, $options);
        }
    }
}