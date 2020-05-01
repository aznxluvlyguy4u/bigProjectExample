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
    const INBREEDING_COEFFICIENT_RUN_ALL_ANIMALS = 'inbreedingCoefficientRunAllAnimals';
    const INBREEDING_COEFFICIENT_RUN_REPORT = 'inbreedingCoefficientRunReport';
    const INBREEDING_COEFFICIENT_RUN_PARENT_PAIRS = 'inbreedingCoefficientRunParentPairs';
    const PROCESS_UNLOCK_ALL = 'processesUnlockAll';
}
