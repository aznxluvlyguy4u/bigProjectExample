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

    //Request repositories
    const DECLARE_ARRIVAL_REPOSITORY = 'AppBundle:DeclareArrival';
    const DECLARE_IMPORT_REPOSITORY = 'AppBundle:DeclareImport';

}