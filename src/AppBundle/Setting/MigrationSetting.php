<?php

namespace AppBundle\Setting;


class MigrationSetting
{
    const EMPTY_PASSWORD_INDICATOR = "NEW_CLIENT";
    const EMPTY_EMAIL_ADDRESS_INDICATOR = "NEW_CLIENT";
    const DEFAULT_EMAIL_DOMAIN = "nsfo.nl";
    const DEFAULT_MIGRATION_PASSWORD = "NSFO-";
    const PASSWORD_LENGTH = 9;
}