<?php

namespace AppBundle\Constant;

class Constant
{
    const jsonNamespace  = 'json';
    const AUTHORIZATION_HEADER_NAMESPACE = 'Authorization';
    const ACCESS_TOKEN_HEADER_NAMESPACE = 'AccessToken';
    const STATE_NAMESPACE = 'state';
    const REQUEST_STATE_NAMESPACE = 'requestState';
    const TAG_STATUS_NAMESPACE = 'tagStatus';
    const TAG_STATUS_IS_TRANSFERRED_NAMESPACE = 'isTransferredToNewOwner';
    const REQUEST_ID_NAMESPACE = 'requestId';
    const RESULT_NAMESPACE = "result";
    const CONTINENT_NAMESPACE = 'continent';
    const LOCATION_NAMESPACE = 'location';
    const MESSAGE_ID_SNAKE_CASE_NAMESPACE = 'message_id';
    const MESSAGE_ID_CAMEL_CASE_NAMESPACE = 'messageId';
    const MESSAGE_NUMBER_SNAKE_CASE_NAMESPACE = 'message_number';
    const MESSAGE_NUMBER_CAMEL_CASE_NAMESPACE = 'messageNumber';
    const ANIMAL_TYPE_NAMESPACE = 'type';
    const TAGS_NAMESPACE = "tags";
    const ANIMAL_NAMESPACE = 'animal';
    const ANIMALTYPE_NAMESPACE = "animalType";
    const ANIMAL_TYPE_SNAKE_CASE_NAMESPACE = "animal_type";
    const TAG_TYPE_NAMESPACE = "tagType";
    const TAG_TYPE_SNAKE_CASE_NAMESPACE = "tag_type";
    const ALL_NAMESPACE = 'all';
    const ALIVE_NAMESPACE = 'alive';
    const IS_ALIVE_NAMESPACE = 'isAlive';
    const BOOLEAN_TRUE_NAMESPACE = 'true';
    const UNKNOWN_NAMESPACE = 'unknown';
    const CODE_NAMESPACE = 'code';
    const ULN_NUMBER_NAMESPACE = "uln_number";
    const PEDIGREE_NUMBER_NAMESPACE = "pedigree_number";
    const ULN_COUNTRY_CODE_NAMESPACE = "uln_country_code";
    const PEDIGREE_SNAKE_CASE_NAMESPACE = "pedigree_number";
    const PEDIGREE_COUNTRY_CODE_NAMESPACE = "pedigree_country_code";
    const PEDIGREE_NAMESPACE = 'pedigree';
    const ULN_NAMESPACE = 'uln';
    const HISTORY_NAMESPACE = 'history';
    const UBN_NAMESPACE = 'ubn';
    const GENDER_NAMESPACE = 'gender';
    const ANIMAL_ORDER_NUMBER_NAMESPACE = 'animal_order_number';
    const UBN_NEW_OWNER_NAMESPACE = 'ubn_new_owner';

    const DATE_OF_BIRTH_NAMESPACE  = 'date_of_birth';
    const DATE_OF_DEATH_NAMESPACE  = 'date_of_death';
    const RELATION_NUMBER_ACCEPTANT_SNAKE_CASE_NAMESPACE = "relation_number_acceptant";

    const FATHER_NAMESPACE = 'father';
    const MOTHER_NAMESPACE = 'mother';
    const CHILDREN_NAMESPACE = 'children';
    const SURROGATE_NAMESPACE = 'surrogate';
    const ASSIGNED_NAMESPACE = 'assigned';
    const UNASSIGNED_NAMESPACE = 'unassigned';

    //Request repositories
    const DECLARE_ARRIVAL_REPOSITORY = 'AppBundle:DeclareArrival';
    const DECLARE_ARRIVAL_RESPONSE_REPOSITORY = 'AppBundle:DeclareArrivalResponse';
    const DECLARE_BIRTH_REPOSITORY = 'AppBundle:DeclareBirth';
    const DECLARE_DEPART_REPOSITORY = 'AppBundle:DeclareDepart';
    const DECLARE_DEPART_RESPONSE_REPOSITORY = 'AppBundle:DeclareDepartResponse';

    const DECLARE_IMPORT_REPOSITORY ='AppBundle:DeclareImport';
    const DECLARE_IMPORT_RESPONSE_REPOSITORY ='AppBundle:DeclareImportResponse';
    const DECLARE_EXPORT_REPOSITORY ='AppBundle:DeclareExport';
    const DECLARE_EXPORT_RESPONSE_REPOSITORY ='AppBundle:DeclareExportResponse';
    const DECLARE_TAGS_TRANSFER_REPOSITORY = 'AppBundle:DeclareTagsTransfer';
    const RETRIEVE_TAGS_REPOSITORY = 'AppBundle:RetrieveTags';
    const DECLARE_LOSS_REPOSITORY = 'AppBundle:DeclareLoss';

    const DECLARE_EARTAGS_TRANSFER_REPOSITORY = 'AppBundle:DeclareEartagsTransfer';
    const DECLARE_BASE_REPOSITORY = 'AppBundle:DeclareBase';
    const DECLARE_BASE_RESPONSE_REPOSITORY = 'AppBundle:DeclareBaseResponse';

    const TAG_REPOSITORY = 'AppBundle:Tag';
    const ANIMAL_REPOSITORY = 'AppBundle:Animal';
    const EWE_REPOSITORY = 'AppBundle:Ewe';
    const RAM_REPOSITORY = 'AppBundle:Ram';
    const NEUTER_REPOSITORY = 'AppBundle:Neuter';

    const PERSON_REPOSITORY = 'AppBundle:Person';
    const CLIENT_REPOSITORY = 'AppBundle:Client';
    const EMPLOYEE_REPOSITORY = 'AppBundle:Employee';

    const COUNTRY_REPOSITORY = 'AppBundle:Country';
    const COMPANY_REPOSITORY = 'AppBundle:Company';
    const LOCATION_REPOSITORY = 'AppBundle:Location';
}