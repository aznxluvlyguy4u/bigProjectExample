<?php

namespace AppBundle\Enumerator;


class UserActionType extends RequestType
{
    const USER_PASSWORD_CHANGE = 'USER_PASSWORD_CHANGE';
    const ADMIN_PASSWORD_CHANGE = 'ADMIN_PASSWORD_CHANGE';
    const USER_PASSWORD_RESET = 'USER_PASSWORD_RESET';
    const ADMIN_PASSWORD_RESET = 'ADMIN_PASSWORD_RESET';
}