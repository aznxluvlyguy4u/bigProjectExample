<?php

namespace AppBundle\Enumerator;

use AppBundle\Traits\EnumInfo;

/**
 * Class ActionType
 * @package AppBundle\Enumerator
 */
class OffspringMaturityType
{
    use EnumInfo;

    const OWN_OFFSPRING_MATURED_AS_OWN_MOTHER = 'OWN_OFFSPRING_MATURED_AT_OWN_MOTHER';
    const OWN_OFFSPRING_MATURED_AT_OTHER_SURROGATE = 'OWN_OFFSPRING_MATURED_AT_OTHER_SURROGATE';
    const OTHER_OFFSPRING_MATURED_AS_SURROGATE = 'OTHER_OFFSPRING_MATURED_AS_SURROGATE';
}
