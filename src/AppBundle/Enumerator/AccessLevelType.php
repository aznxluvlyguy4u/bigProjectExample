<?php

namespace AppBundle\Enumerator;

use AppBundle\Traits\EnumInfo;

/**
 * Class ActionType
 * @package AppBundle\Enumerator
 */
class AccessLevelType
{
    use EnumInfo;

    const DEVELOPER = 'DEVELOPER';
    const SUPER_ADMIN = 'SUPER_ADMIN';
    const ADMIN = 'ADMIN';
}