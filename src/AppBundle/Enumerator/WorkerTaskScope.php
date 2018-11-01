<?php


namespace AppBundle\Enumerator;


class WorkerTaskScope
{
    const COMPLETE = 'COMPLETE';
    const COMPLETE_INCLUDING_ASCENDANTS = 'COMPLETE_INCLUDING_ASCENDANTS';

    //Reproduction
    const BIRTH_AS_PARENT = 'BIRTH_AS_PARENT';
    const BIRTH_AS_CHILD = 'BIRTH_AS_CHILD';
    const N_LING = 'N_LING';
    const PRODUCTION = 'PRODUCTION';

    //Declares
    const ARRIVAL = 'ARRIVAL';
    const BIRTH = 'BIRTH';
    const BIRTH_REVOKE = 'BIRTH_REVOKE';
    const DEPART = 'DEPART';
    const EXPORT = 'EXPORT';
    const IMPORT = 'IMPORT';
    const LOSS = 'LOSS';
    const REVOKE = 'REVOKE';
    const TAG_REPLACE = 'TAG_REPLACE';
    const TAG_TRANSFER = 'TAG_TRANSFER';

    //Measurements
    const EXTERIOR = 'EXTERIOR';
    const WEIGHT = 'WEIGHT';
}