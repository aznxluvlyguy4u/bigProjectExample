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
    const APP_BUNDLE = 'AppBundle';
    const DECLARE_ARRIVAL_REPOSITORY = self::APP_BUNDLE . ':' . 'DeclareArrival';
    const DECLARE_BIRTH_REPOSITORY = self::APP_BUNDLE . ':' . 'DeclareBirth';
    const DECLARE_DEPART_REPOSITORY = self::APP_BUNDLE . ':' . 'DeclareDepart';
    const DECLARE_IMPORT_REPOSITORY = self::APP_BUNDLE . ':' . 'DeclareImport';
    const DECLARE_EARTAGS_TRANSFER_REPOSITORY = self::APP_BUNDLE . ':' . 'DeclareEartagsTransfer';
}