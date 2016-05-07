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
    const ANIMAL_TYPE_NAMESPACE = 'type';
    const ALL_NAMESPACE = 'all';
    const ALIVE_NAMESPACE = 'alive';
    const IS_ALIVE_NAMESPACE = 'isAlive';
    const BOOLEAN_TRUE_NAMESPACE = 'true';
    const UNKNOWN_NAMESPACE = 'unknown';

    //Request repositories
    const DECLARE_ARRIVAL_REPOSITORY = 'AppBundle:DeclareArrival';
    const DECLARE_BIRTH_REPOSITORY = 'AppBundle:DeclareBirth';
    const DECLARE_DEPART_REPOSITORY = 'AppBundle:DeclareDepart';
    const DECLARE_IMPORT_REPOSITORY ='AppBundle:DeclareImport';
    const DECLARE_EARTAGS_TRANSFER_REPOSITORY = 'AppBundle:DeclareEartagsTransfer';
    const DECLARE_LOSS_REPOSITORY = 'AppBundle:DeclareLoss';

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