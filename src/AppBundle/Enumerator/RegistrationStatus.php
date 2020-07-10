<?php


namespace AppBundle\Enumerator;


use AppBundle\Traits\EnumInfo;

class RegistrationStatus
{
    use EnumInfo;

    const NEW = 'NEW';
    const FAILED_SENDING_EMAILS = 'FAILED_SENDING_EMAILS';
    const REJECTED = 'REJECTED';
    const COMPLETED = 'COMPLETED';
}
