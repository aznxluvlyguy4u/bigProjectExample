<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class DashboardType
{
    use EnumInfo;

    const ADMIN = 'admin';
    const CLIENT = 'client';
    const VWA = 'vwa';
}