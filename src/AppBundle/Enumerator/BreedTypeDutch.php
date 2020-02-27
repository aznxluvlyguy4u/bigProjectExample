<?php

namespace AppBundle\Enumerator;

use AppBundle\Traits\EnumInfo;

class BreedTypeDutch
{
    use EnumInfo;

    const BLIND_FACTOR = "ERFELIJKE AFWIJKING"; // TODO remove this when BLIND_FACTOR is replaced in the database
    // const HEREDITARY_DEVIATION = "ERFELIJKE AFWIJKING"; TODO only add this if BLIND_FACTOR is replaced in the database
    const MEAT_LAMB_FATHER = "VLEESLAMVADERDIER";
    const MEAT_LAMB_MOTHER = "VLEESLAMMOEDERDIER";
    const PARENT_ANIMAL = "OUDERDIER";
    const PURE_BRED = "VOLBLOED";
    const REGISTER = "REGISTER";
    const SECONDARY_REGISTER = "HULPBOEK";
    const UNDETERMINED = "ONBEPAALD";
    const EN_MANAGEMENT = "EN-MANAGEMENT";
    const EN_BASIS = "EN-BASIS";

}
