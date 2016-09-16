<?php

namespace AppBundle\Constant;


class JsonInputConstant
{
    //Admins
    const ADMINS = 'admins';
    const ACCESS_LEVEL = 'access_level';

    //Animal
    const WORK_NUMBER = 'work_number';
    const IS_ALIVE = 'is_alive';
    const ANIMAL = 'animal';
    const RAM = 'ram';
    const EWE = 'ewe';
    const ULN_COUNTRY_CODE = 'uln_country_code';
    const ULN_NUMBER = 'uln_number';
    const PEDIGREE_COUNTRY_CODE = 'pedigree_country_code';
    const PEDIGREE_NUMBER = 'pedigree_number';

    //Request & Response
    const ERROR_CODE = 'error_code';
    const ERROR_MESSAGE = 'error_message';
    const ERROR_KIND_INDICATOR = 'error_kind_indicator';
    const SUCCESS_INDICATOR = 'success_indicator';
    const ACTION = 'action';
    const RECOVERY_INDICATOR = 'recovery_indicator';
    const ACTION_BY = 'action_by';
    const IS_REMOVED_BY_USER = 'is_removed_by_user';

    //Arrival & Import
    const IS_IMPORT_ANIMAL = 'is_import_animal';
    const UBN_PREVIOUS_OWNER = 'ubn_previous_owner';
    const ARRIVAL_DATE = 'arrival_date';
    const IS_ARRIVED_FROM_OTHER_NSFO_CLIENT = 'is_arrived_from_other_nsfo_client';
    const COUNTRY_ORIGIN = 'country_origin';

    //Depart & Export
    const IS_EXPORT_ANIMAL = 'is_export_animal';
    const UBN_NEW_OWNER = 'ubn_new_owner';
    const DEPART_DATE = 'depart_date';
    const REASON_OF_DEPARTURE = 'reason_of_departure';

    //Tags
    const RELATION_NUMBER_ACCEPTANT = "relation_number_acceptant";
    const TAGS = "tags";
    const TAG = "tag";

    //Mate
    const PMSG = 'pmsg';
    const KI = 'ki';
    const START_DATE = 'start_date';
    const END_DATE = 'end_date';

    //Authentication
    const NEW_PASSWORD = 'new_password';

    //Clients
    const FIRST_NAME = 'first_name';
    const LAST_NAME = "last_name";
    const EMAIL_ADDRESS = "email_address";
    const IS_PRIMARY_CONTACT_PERSON = "is_primary_contact_person";
    const PREFIX = "prefix";

    const PASSWORD = "password";
    const USERNAME = "username";
    const CELLPHONE_NUMBER = "cellphone_number";
    const RELATION_NUMBER_KEEPER = "relation_number_keeper";

    //Content Management System (cms)
    const DASHBOARD = 'dashboard';
    const CONTACT_INFO = 'contact_info';

    //Company
    const COMPANY_ID = 'company_id';
    const COMPANY_NAME = 'company_name';
    const VAT_NUMBER = 'vat_number';
    const CHAMBER_OF_COMMERCE_NUMBER = 'chamber_of_commerce_number';
    const COMPANY_RELATION_NUMBER = 'company_relation_number';

    //Health
    const MAEDI_VISNA_STATUS = 'maedi_visna_status';
    const MAEDI_VISNA_START_DATE = 'maedi_visna_start_date';
    const MAEDI_VISNA_CHECK_DATE = 'maedi_visna_check_date';
    const MAEDI_VISNA_END_DATE = 'maedi_visna_end_date';
    const SCRAPIE_STATUS = 'scrapie_status';
    const SCRAPIE_START_DATE = 'scrapie_start_date';
    const SCRAPIE_CHECK_DATE = 'scrapie_check_date';
    const SCRAPIE_END_DATE = 'scrapie_end_date';

    //Locations
    const PROVINCES = 'provinces';
    const CODES = 'codes';

    //Loss
    const UBN_DESTRUCTOR = "ubn_destructor";

    //Location / UBN
    const UBN = 'ubn';

    //Messages
    const MESSAGE_ID = 'message_id';
    const REQUEST_ID = 'request_id';
    const REQUEST_STATE = 'request_state';
    const MESSAGE_NUMBER = 'message_number';
    const LOG_DATE = 'log_date';
    const RESULT = 'result';
    const IS_HIDDEN = 'is_hidden';
    const IS_OVERWRITTEN = 'is_overwritten';
    const REVOKED_BY = 'revoked_by';
    const REVOKE_DATE = 'revoke_date';

    //Persons
    const PERSON_ID = 'person_id';

    //Weight measurements
    const DATE_OF_MEASUREMENT = 'date_of_measurement';
    const WEIGHT_MEASUREMENTS = 'weight_measurements';
    const WEIGHT = 'weight';
    const IS_BIRTH_WEIGHT = 'is_birth_weight';
    const MEASUREMENT_DATE = 'measurement_date';



}