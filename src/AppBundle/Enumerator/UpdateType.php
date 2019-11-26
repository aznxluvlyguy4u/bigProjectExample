<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class UpdateType
{
    use EnumInfo;

    /**
     * Note that these enum values should match the UpdateType enum class in the frontend.
     * The enum values do not have to match the report numbers as listed in the JVT-NSFO overview
     */
    const STAR_EWES = 1;
}
