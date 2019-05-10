<?php

namespace AppBundle\Enumerator;

use AppBundle\Traits\EnumInfo;

/**
 * Class ActionType
 * @package AppBundle\Enumerator
 */
class MainCommandNamedOptions
{
    use EnumInfo;

    const DATABASE_SEQUENCE_UPDATE = 'databaseSequenceUpdate';
    const PROCESS_UNLOCK_ALL = 'processesUnlockAll';
}