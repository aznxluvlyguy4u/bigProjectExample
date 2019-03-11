<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class SqsCommandType
{
    use EnumInfo;

    const SYNC_HEALTH_CHECK = 1;
    const SYNC_ANIMAL_RELOCATION = 2;
    const BATCH_INVOICE_GENERATION = 3;
}