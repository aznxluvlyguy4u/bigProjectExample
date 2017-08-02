<?php

namespace AppBundle\Enumerator;

/**
 * Class ControllerTypes
 * @package AppBundle\Enumerator
 */
class ServiceId
{
    const ANIMAL_LOCATION_HISTORY = 'app.animallocation.history';
    const CLIENT_MIGRATOR = 'app.migrator.client';
    const ENTITY_GETTER = 'app.doctrine.entitygetter';
    const EXTERNAL_QUEUE_SERVICE = 'app.aws.queueservice.external';
    const EXCEL_SERVICE = 'app.excel.service';
    const HEALTH_SERVICE = 'app.health.updater';
    const INTERNAL_QUEUE_SERVICE = 'app.aws.queueservice.internal';
    const LOGGER = 'logger';
    const MIXBLUP_INPUT_QUEUE_SERVICE = 'app.aws.queueservice.mixblub_input';
    const MIXBLUP_OUTPUT_QUEUE_SERVICE = 'app.aws.queueservice.mixblub_output';
    const PEDIGREE_REGISTER_REPORT = 'app.report.pedigree_register';
    const REDIS_CLIENT = 'snc_redis.sncredis';
    const SERIALIZER = 'app.serializer.ir';
    const STORAGE_SERVICE = 'app.aws.storageservice';
}