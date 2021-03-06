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

    /**
     * The Inbreeding Coefficient Processes are run separately in their own more complex worker process.
     */
    const INBREEDING_COEFFICIENT_CALCULATION = 2;
    const INBREEDING_COEFFICIENT_RECALCULATION = 3;
}
