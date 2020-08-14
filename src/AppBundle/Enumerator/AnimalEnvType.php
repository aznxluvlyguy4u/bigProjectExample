<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class AnimalEnvType
{
    use EnumInfo;

    const GOAT = 'goat';
    const SHEEP = 'sheep';
}