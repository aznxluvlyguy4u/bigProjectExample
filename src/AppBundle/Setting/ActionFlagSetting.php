<?php

namespace AppBundle\Setting;


use AppBundle\Enumerator\ActionType;
use AppBundle\Enumerator\RequestStateType;

class ActionFlagSetting
{
    const DEFAULT_ACTION = ActionType::V;

    //If null, the default action is used.
    //If not null, the value below will override the default value.
    const DECLARE_ARRIVAL = null;
    const DECLARE_BIRTH = null;
    const DECLARE_DEPART = null;
    const DECLARE_EXPORT = null;
    const DECLARE_IMPORT = null;
    const DECLARE_LOSS = null;
    const REVOKE_DECLARATION = null;
    const RETRIEVE_ANIMAL_DETAILS = null;
    const RETRIEVE_ANIMAL = null;
    const RETRIEVE_UBN_DETAILS = null;
    const TAG_SYNC = null;
    const TAG_TRANSFER = null;

    //TODO The messages belonging to the request types below are not implemented yet
    const DECLARATION_DETAIL = null;
    const DECLARE_ANIMAL_FLAG = null;


    const RECOVERY_FLAG_TRUE = 'J';
    const RECOVERY_FLAG_FALSE = 'N';
    const ACTION_TYPE_READ_ONLY = 'C';
    const ACTION_TYPE_MUTATE = 'V';

}