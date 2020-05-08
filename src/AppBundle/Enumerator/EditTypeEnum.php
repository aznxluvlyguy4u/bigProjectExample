<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class EditTypeEnum
{
    use EnumInfo;

    const ADMIN_EDIT = 0;
    const ADMIN_CREATE = 1;
    const DEV_DATABASE_EDIT = 2;
    const WORKER_EDIT = 3;
    const CLOSE_END_DATE_BY_NEXT_RECORD = 4;
    const CLOSE_END_DATE_BY_DATE_OF_DEATH = 5;
    const CLOSE_END_DATE_BY_CRON_FIX_REMOVED_ANIMAL = 6;
    const CLOSE_END_DATE_BY_CRON_FIX_RELOCATED_ANIMAL = 7;
}
