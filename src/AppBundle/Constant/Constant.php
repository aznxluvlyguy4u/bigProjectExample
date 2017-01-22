<?php

namespace AppBundle\Constant;

class Constant
{
    //Auth
    const AUTHORIZATION_HEADER_NAMESPACE = 'Authorization';
    const ACCESS_TOKEN_HEADER_NAMESPACE = 'AccessToken';
    const GHOST_TOKEN_HEADER_NAMESPACE = 'GhostToken';

    //DeclareBase
    const STATE_NAMESPACE = 'state';
    const REQUEST_STATE_NAMESPACE = 'requestState';
    const REQUEST_ID_NAMESPACE = 'requestId';

    //Location
    const CONTINENT_NAMESPACE = 'continent';
    const LOCATION_NAMESPACE = 'location';

    //Message identification
    const MESSAGE_ID_SNAKE_CASE_NAMESPACE = 'message_id';
    const MESSAGE_ID_CAMEL_CASE_NAMESPACE = 'messageId';
    const MESSAGE_NUMBER_SNAKE_CASE_NAMESPACE = 'message_number';
    const MESSAGE_NUMBER_CAMEL_CASE_NAMESPACE = 'messageNumber';

    //Identity
    const NAME_NAMESPACE = 'name';

    //Array
    const KEY = 'key';

    //Json response
    const MESSAGE_NAMESPACE = 'message';
    const RESULT_NAMESPACE = "result";
    const jsonNamespace  = 'json';
    const COUNT = 'count';

    //Tags
    const TAGS_NAMESPACE = "tags";
    const TAG_NAMESPACE = "tag";
    const TAG_TYPE_NAMESPACE = "tagType";
    const TAG_TYPE_SNAKE_CASE_NAMESPACE = "tag_type";
    const TAG_STATUS_NAMESPACE = 'tagStatus';
    const TAG_STATUS_IS_TRANSFERRED_NAMESPACE = 'isTransferredToNewOwner';

    //Verification
    const ALL_NAMESPACE = 'all';
    const ALIVE_NAMESPACE = 'alive';
    const IS_ALIVE_NAMESPACE = 'isAlive';
    const BOOLEAN_TRUE_NAMESPACE = 'true';
    const UNKNOWN_NAMESPACE = 'unknown';
    const VALIDITY_NAMESPACE = 'validity';
    const IS_VALID_NAMESPACE = 'isValid';
    const RESPONSE = 'response';
    const CODE_NAMESPACE = 'code';
    const ERRORS_NAMESPACE = 'errors';
    const TYPE_NAMESPACE = 'type';
    const GHOST_TOKEN_NAMESPACE = 'ghost_token';

    //Animal identification
    const ULN_NAMESPACE = 'uln';
    const ULN_NUMBER_NAMESPACE = "uln_number";
    const ULN_COUNTRY_CODE_NAMESPACE = "uln_country_code";
    const PEDIGREE_NAMESPACE = 'pedigree';
    const PEDIGREE_NUMBER_NAMESPACE = "pedigree_number";
    const PEDIGREE_SNAKE_CASE_NAMESPACE = "pedigree_number";
    const PEDIGREE_COUNTRY_CODE_NAMESPACE = "pedigree_country_code";
    const LITTER_GROUP_NAMESPACE = "litter_group";
    const LITTER_SIZE_NAMESPACE = "litter_size";

    //Other animal variables
    const HISTORY_NAMESPACE = 'history';
    const UBN_NAMESPACE = 'ubn';
    const GENDER_NAMESPACE = 'gender';
    const ANIMAL_ORDER_NUMBER_NAMESPACE = 'animal_order_number';
    const UBN_NEW_OWNER_NAMESPACE = 'ubn_new_owner';
    const UBN_PREVIOUS_OWNER_NAMESPACE = 'ubn_previous_owner';
    const ANIMAL_TYPE_NAMESPACE = 'type';
    const ANIMAL_NAMESPACE = 'animal';
    const ANIMALS_NAMESPACE = 'animals';
    const ANIMALTYPE_NAMESPACE = "animalType";
    const ANIMAL_TYPE_SNAKE_CASE_NAMESPACE = "animal_type";
    const DATE_OF_BIRTH_NAMESPACE  = 'date_of_birth';
    const DATE_OF_DEATH_NAMESPACE  = 'date_of_death';
    const IS_IMPORT_ANIMAL = 'is_import_animal';

    //Health Status
    const ILLNESSES = "illnesses";
    const MAEDI_VISNA = "maedi_visna";
    const MAEDI_VISNA_STATUS = "maedi_visna_status";
    const MAEDI_VISNA_END_DATE = "maedi_visna_end_date";
    const SCRAPIE = "scrapie";
    const SCRAPIE_STATUS = "scrapie_status";
    const SCRAPIE_END_DATE = "scrapie_end_date";
    const CHECK_DATE = "check_date";
    const LOCATION_HEALTH_ORIGIN = "location_health_origin";
    const LOCATION_HEALTH_DESTINATION = "location_health_destination";

    //Declaration specific variables
    const RELATION_NUMBER_ACCEPTANT_SNAKE_CASE_NAMESPACE = "relation_number_acceptant";

    //Animal family members
    const FATHER_NAMESPACE = 'father';
    const MOTHER_NAMESPACE = 'mother';
    const CHILDREN_NAMESPACE = 'children';
    const SURROGATE_NAMESPACE = 'surrogate';
    const SURROGATE_CHILDREN_NAMESPACE = 'surrogate_children';

    //Process runtime
    const RUN_TIME_LIMIT_IN_MINUTES = 'run_time_limit_in_minutes';
    const TEST_RUN_WITH_DEFAULT_UBNS = 'test_run_with_default_ubns';

    //Request repositories
    const DECLARE_ARRIVAL_REPOSITORY = 'AppBundle:DeclareArrival';
    const DECLARE_ARRIVAL_RESPONSE_REPOSITORY = 'AppBundle:DeclareArrivalResponse';
    const DECLARE_BIRTH_REPOSITORY = 'AppBundle:DeclareBirth';
    const DECLARE_BIRTH_RESPONSE_REPOSITORY = 'AppBundle:DeclareBirthResponse';
    const DECLARE_DEPART_REPOSITORY = 'AppBundle:DeclareDepart';
    const DECLARE_DEPART_RESPONSE_REPOSITORY = 'AppBundle:DeclareDepartResponse';

    const DECLARE_IMPORT_REPOSITORY ='AppBundle:DeclareImport';
    const DECLARE_IMPORT_RESPONSE_REPOSITORY ='AppBundle:DeclareImportResponse';
    const DECLARE_EXPORT_REPOSITORY ='AppBundle:DeclareExport';
    const DECLARE_EXPORT_RESPONSE_REPOSITORY ='AppBundle:DeclareExportResponse';
    const DECLARE_TAGS_TRANSFER_REPOSITORY = 'AppBundle:DeclareTagsTransfer';
    const DECLARE_TAG_REPLACE_REPOSITORY = 'AppBundle:DeclareTagReplace';
    const RETRIEVE_TAGS_REPOSITORY = 'AppBundle:RetrieveTags';
    const DECLARE_LOSS_REPOSITORY = 'AppBundle:DeclareLoss';
    const DECLARE_LOSS_RESPONSE_REPOSITORY = 'AppBundle:DeclareLossResponse';

    const DECLARE_EARTAGS_TRANSFER_REPOSITORY = 'AppBundle:DeclareEartagsTransfer';
    const DECLARE_BASE_REPOSITORY = 'AppBundle:DeclareBase';
    const DECLARE_BASE_RESPONSE_REPOSITORY = 'AppBundle:DeclareBaseResponse';

    const TAG_REPOSITORY = 'AppBundle:Tag';
    const ANIMAL_REPOSITORY = 'AppBundle:Animal';
    const EWE_REPOSITORY = 'AppBundle:Ewe';
    const RAM_REPOSITORY = 'AppBundle:Ram';
    const NEUTER_REPOSITORY = 'AppBundle:Neuter';
    const MATE_REPOSITORY = 'AppBundle:Mate';


    const PERSON_REPOSITORY = 'AppBundle:Person';
    const CLIENT_REPOSITORY = 'AppBundle:Client';
    const EMPLOYEE_REPOSITORY = 'AppBundle:Employee';

    const COUNTRY_REPOSITORY = 'AppBundle:Country';
    const COLLAR_REPOSITORY = 'AppBundle:Collar';
    const COMPANY_REPOSITORY = 'AppBundle:Company';
    const LOCATION_REPOSITORY = 'AppBundle:Location';

    const LOCATION_HEALTH_REPOSITORY = 'AppBundle:LocationHealth';
    const PROCESSOR_REPOSITORY = 'AppBundle:Processor';
    const PROVINCE_REPOSITORY = 'AppBundle:Province';
    const LOCATION_HEALTH_QUEUE_REPOSITORY = 'AppBundle:LocationHealthQueue';

    const ANIMAL_RESIDENCE_REPOSITORY = 'AppBundle:AnimalResidence';

    const NEW_PASSWORD_MAIL_SUBJECT_HEADER = "Uw nieuwe NSFO Online wachtwoord";
    const NEW_ADMIN_PASSWORD_MAIL_SUBJECT_HEADER = "Uw nieuwe NSFO Online ADMIN wachtwoord";
    const CONTACT_CONFIRMATION_MAIL_SUBJECT_HEADER = 'NSFO Online Contactformulier Confirmatie';
    
    const ENTITY_BASE_PATH = "AppBundle\\Entity\\";

    const CACHE_DEFAULT_TIME_SPAN = 3600;
    const CACHE_LIVESTOCK_TIME_SPAN = Constant::CACHE_DEFAULT_TIME_SPAN;
    const CACHE_HISTORIC_LIVESTOCK_TIME_SPAN = Constant::CACHE_DEFAULT_TIME_SPAN;
    const CACHE_CANDIDATE_FATHERS_TIME_SPAN = Constant::CACHE_DEFAULT_TIME_SPAN;
}