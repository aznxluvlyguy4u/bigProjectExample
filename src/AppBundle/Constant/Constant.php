<?php

namespace AppBundle\Constant;

class Constant
{
    const jsonNamespace  = 'json';
    const AUTHORIZATION_HEADER_NAMESPACE = 'Authorization';
    const ACCESS_TOKEN_HEADER_NAMESPACE = 'AccessToken';
    const STATE_NAMESPACE = 'state';
    const REQUEST_STATE_NAMESPACE = 'requestState';
    const REQUEST_ID_NAMESPACE = 'requestId';
    const RESULT_NAMESPACE = "result";
    const CONTINENT_NAMESPACE = 'continent';
    const LOCATION_NAMESPACE = 'location';
    const MESSAGE_ID_SNAKE_CASE_NAMESPACE = 'message_id';
    const MESSAGE_ID_CAMEL_CASE_NAMESPACE = 'messageId';
    const ANIMAL_TYPE_NAMESPACE = 'type';
    const ANIMAL_NAMESPACE = 'animal';
    const ALL_NAMESPACE = 'all';
    const ALIVE_NAMESPACE = 'alive';
    const IS_ALIVE_NAMESPACE = 'isAlive';
    const BOOLEAN_TRUE_NAMESPACE = 'true';
    const UNKNOWN_NAMESPACE = 'unknown';
    const CODE_NAMESPACE = 'code';
    const ULN_NUMBER_NAMESPACE = "uln_number";
    const PEDIGREE_NUMBER_NAMESPACE = "pedigree_number";
    const ULN_COUNTRY_CODE_NAMESPACE = "uln_country_code";
    const PEDIGREE_COUNTRY_CODE_NAMESPACE = "pedigree_country_code";
    const PEDIGREE_NAMESPACE = 'pedigree';
    const ULN_NAMESPACE = 'uln';
    const UBN_NAMESPACE = 'ubn';
    const GENDER_NAMESPACE = 'gender';
    const FATHER_NAMESPACE = 'father';
    const MOTHER_NAMESPACE = 'mother';
    const CHILDREN_NAMESPACE = 'children';
    const SURROGATE_NAMESPACE = 'surrogate';
    const ASSIGNED_NAMESPACE = 'assigned';
    const UNASSIGNED_NAMESPACE = 'unassigned';

    //Request repositories
    const DECLARE_ARRIVAL_REPOSITORY = 'AppBundle:DeclareArrival';
    const DECLARE_BIRTH_REPOSITORY = 'AppBundle:DeclareBirth';
    const DECLARE_DEPART_REPOSITORY = 'AppBundle:DeclareDepart';
    const DECLARE_EARTAGS_TRANSFER_REPOSITORY = 'AppBundle:DeclareEartagsTransfer';
    const DECLARE_IMPORT_REPOSITORY ='AppBundle:DeclareImport';
    const DECLARE_LOSS_REPOSITORY = 'AppBundle:DeclareLoss';
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

    const RESPONSE_ULN_NOT_FOUND = array("code"=>428,"message"=>"Given Uln & Country code is invalid, it is not registered to a known Tag");
}