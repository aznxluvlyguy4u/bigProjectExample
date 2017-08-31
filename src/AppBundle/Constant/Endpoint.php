<?php

namespace AppBundle\Constant;


class Endpoint
{
    const ACTION_LOG = '/api/v1/log/action';

    const DECLARE_ARRIVAL_ENDPOINT = "/api/v1/arrivals";
    const DECLARE_BIRTH_ENDPOINT = "/api/v1/births";
    const DECLARE_IMPORT_ENDPOINT = "/api/v1/imports";
    const DECLARE_DEPART_ENDPOINT = "/api/v1/departs";
    const DECLARE_LOSSES_ENDPOINT = "/api/v1/losses";
    const DECLARE_MATINGS_ENDPOINT = "/api/v1/matings";
    const DECLARE_TAGS_TRANSFERS_ENDPOINT = "/api/v1/tags-transfers";
    const DECLARE_TAG_REPLACE_ENDPOINT = "/api/v1/tags-replace";

    const ERROR_ENDPOINT = '/api/v1/errors';

    const RETRIEVE_ANIMALS = '/api/v1/animals-sync';
    const RETRIEVE_TAGS = '/api/v1/tags-sync';

    const REVOKE_ENDPOINT = "/api/v1/revokes";

    const TREATMENTS = "/api/v1/treatments";
    const TREATMENT_TYPES = "/api/v1/treatment-types";
}