<?php

namespace AppBundle\Enumerator;


class UserActionType extends RequestType
{
    const USER_PASSWORD_CHANGE = 'USER_PASSWORD_CHANGE';
    const ADMIN_PASSWORD_CHANGE = 'ADMIN_PASSWORD_CHANGE';
    const USER_PASSWORD_RESET = 'USER_PASSWORD_RESET';
    const ADMIN_PASSWORD_RESET = 'ADMIN_PASSWORD_RESET';
    const USER_LOGIN = 'USER_LOGIN';
    const ADMIN_LOGIN = 'ADMIN_LOGIN';
    
    const PROFILE_UPDATE = 'PROFILE_UPDATE';
    const CONTACT_EMAIL = 'CONTACT_EMAIL';
    const DASHBOARD_INTRO_TEXT_UPDATE = 'DASHBOARD_INTRO_TEXT_UPDATE';
    const CONTACT_INFO_UPDATE = 'CONTACT_INFO_UPDATE';

    const ANIMAL_DETAILS_EDIT = 'ANIMAL_DETAILS_EDIT';
    const GENDER_CHANGE = 'GENDER_CHANGE';
    
    const HEALTH_STATUS_UPDATE = 'HEALTH_STATUS_UPDATE';

    const MATE_CREATE = 'MATE_CREATE';
    const MATE_EDIT = 'MATE_EDIT';

    const BIRTH_CREATE = 'BIRTH_CREATE';
    const BIRTH_REVOKE = 'BIRTH_REVOKE';
    const FALSE_BIRTH_CREATE = 'FALSE_BIRTH_CREATE';

    const DECLARE_WEIGHT_CREATE = 'DECLARE_WEIGHT_CREATE';
    const DECLARE_WEIGHT_EDIT = 'DECLARE_WEIGHT_EDIT';

    const NON_IR_REVOKE = 'NON_IR_REVOKE';
    
    const CREATE_ADMIN = 'CREATE_ADMIN';
    const EDIT_ADMIN = 'EDIT_ADMIN';
    const DEACTIVATE_ADMIN = 'DEACTIVATE_ADMIN';


    const TREATMENT_TEMPLATE_CREATE = 'TREATMENT_TEMPLATE_CREATE';
    const TREATMENT_TEMPLATE_EDIT = 'TREATMENT_TEMPLATE_EDIT';
    const TREATMENT_TEMPLATE_DELETE = 'TREATMENT_TEMPLATE_DELETE';

    const TREATMENT_TYPE_CREATE = 'TREATMENT_TYPE_CREATE';
    const TREATMENT_TYPE_EDIT = 'TREATMENT_TYPE_EDIT';
    const TREATMENT_TYPE_DELETE = 'TREATMENT_TYPE_DELETE';

    const CREATE_COMPANY = 'CREATE_COMPANY';
    const EDIT_COMPANY = 'EDIT_COMPANY';
    const DEACTIVATE_COMPANY = 'DEACTIVATE_COMPANY';
    const ACTIVATE_COMPANY = 'ACTIVATE_COMPANY';

    const CHANGE_READ_MESSAGE_STATUS = 'CHANGE_READ_MESSAGE_STATUS';
    const CHANGE_HIDE_MESSAGE_STATUS = 'CHANGE_HIDE_MESSAGE_STATUS';

    const CREATE_EXTERIOR = 'CREATE_EXTERIOR';
    const EDIT_EXTERIOR = 'EDIT_EXTERIOR';
    const DEACTIVATE_EXTERIOR = 'DEACTIVATE_EXTERIOR';

    const CREATE_INSPECTION = 'CREATE_INSPECTION';
    const CHANGE_INSPECTION_STATUS = 'CHANGE_INSPECTION_STATUS';


    /**
     * @return array
     */
    public static function getRvoMessageActionTypes()
    {
        $types = parent::getConstants();

        $nonRequestTypeRvoMessageTypes =
            [
                self::BIRTH_CREATE,
                self::BIRTH_REVOKE
            ];

        foreach ($nonRequestTypeRvoMessageTypes as $value) {
            $types[$value] = $value;
        }
        return $types;
    }

}