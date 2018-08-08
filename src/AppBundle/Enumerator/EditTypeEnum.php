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
}