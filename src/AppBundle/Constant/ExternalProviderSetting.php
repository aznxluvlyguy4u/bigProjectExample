<?php


namespace AppBundle\Constant;


class ExternalProviderSetting
{
    const MAX_RE_AUTHENTICATION_TRIES = 10000;
    const RE_LOGON_ERROR_MESSAGE = "Your logon credentials are not valid anymore. Try to log on again.";
    const EXTERNAL_PROVIDER_OFFICE_LIST_CACHE_ID = "twinfield_office_list";
    const EXTERNAL_PROVIDER_OFFICE_CUSTOMER_POSTFIX_CACHE_ID = "_customers";
}