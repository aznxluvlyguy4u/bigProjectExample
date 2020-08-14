<?php

namespace AppBundle\Constant;


use AppBundle\Traits\EnumInfo;

class Environment
{
    use EnumInfo;

    const PROD = 'prod';
    const STAGE = 'stage';
    const DEV = 'dev';
    const TEST = 'test';
    const LOCAL = 'local';
    
}