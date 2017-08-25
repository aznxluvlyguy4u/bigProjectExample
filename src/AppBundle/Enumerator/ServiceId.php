<?php

namespace AppBundle\Enumerator;

/**
 * Class ControllerTypes
 * @package AppBundle\Enumerator
 */
class ServiceId
{
    const ADMIN_SERVICE = 'app.admin';
    const ADMIN_AUTH_SERVICE = 'app.security.admin_auth';
    const ADMIN_PROFILE_SERVICE = 'app.admin.profile';
    const ANIMAL_LOCATION_HISTORY = 'app.animallocation.history';
    const ANIMAL_SERVICE = 'app.animal';
    const ARRIVAL_SERVICE = 'app.arrival';
    const AUTH_SERVICE = 'app.security.auth';
    const BIRTH_SERVICE = 'app.birth';
    const BREED_VALUES_OVERVIEW_REPORT = 'app.report.breed_values_overview';
    const CACHE = 'app.cache';
    const CLIENT_MIGRATOR = 'app.migrator.client';
    const CLIENT_SERVICE = 'app.client';
    const EMAIL_SERVICE = 'app.email';
    const ENTITY_GETTER = 'app.doctrine.entitygetter';
    const EXTERNAL_QUEUE_SERVICE = 'app.aws.queueservice.external';
    const EXCEL_SERVICE = 'app.excel.service';
    const HEALTH_UPDATER_SERVICE = 'app.health.updater';
    const INBREEDING_COEFFICIENT_REPORT_SERVICE = 'app.report.inbreeding_coefficient';
    const INTERNAL_QUEUE_SERVICE = 'app.aws.queueservice.internal';
    const LIVESTOCK_REPORT = 'app.report.livestock';
    const LOGGER = 'logger';
    const MIXBLUP_INPUT_QUEUE_SERVICE = 'app.aws.queueservice.mixblub_input';
    const MIXBLUP_OUTPUT_QUEUE_SERVICE = 'app.aws.queueservice.mixblub_output';
    const PEDIGREE_CERTIFICATES_REPORT = 'app.report.pedigree_certificates';
    const PEDIGREE_REGISTER_REPORT = 'app.report.pedigree_register';
    const REDIS_CLIENT = 'snc_redis.sncredis';
    const REQUEST_MESSAGE_BUILDER = 'app.request.message_builder';
    const SERIALIZER = 'app.serializer.ir';
    const STORAGE_SERVICE = 'app.aws.storageservice';
    const USER_SERVICE = 'app.user';
}