<?php

namespace AppBundle\Enumerator;


class UserActionType extends RequestType
{
    const USER_PASSWORD_CHANGE = 'USER_PASSWORD_CHANGE';
    const ADMIN_PASSWORD_CHANGE = 'ADMIN_PASSWORD_CHANGE';
    const USER_PASSWORD_RESET = 'USER_PASSWORD_RESET';
    const ADMIN_PASSWORD_RESET = 'ADMIN_PASSWORD_RESET';
    
    const PROFILE_UPDATE = 'PROFILE_UPDATE';
    const CONTACT_EMAIL = 'CONTACT_EMAIL';
    
    const HEALTH_STATUS_UPDATE = 'HEALTH_STATUS_UPDATE';

    const MATE_CREATE = 'MATE_CREATE';
    const MATE_EDIT = 'MATE_EDIT';

    const BIRTH_CREATE = 'BIRTH_CREATE';
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
}