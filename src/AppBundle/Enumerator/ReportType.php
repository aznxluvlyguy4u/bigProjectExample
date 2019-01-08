<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class ReportType
{
    use EnumInfo;

    const ANNUAL_ACTIVE_LIVE_STOCK = 1;
    const ANNUAL_TE_100 = 2;
    const FERTILIZER_ACCOUNTING = 3;
    const INBREEDING_COEFFICIENT = 4;
    const PEDIGREE_CERTIFICATE = 5;
    const ANIMALS_OVERVIEW = 6;
    const ANNUAL_ACTIVE_LIVE_STOCK_RAM_MATES = 7;
    const OFFSPRING = 8;
    const PEDIGREE_REGISTER_OVERVIEW = 9;
    const LIVE_STOCK = 10;
    const BIRTH_LIST = 11;
}