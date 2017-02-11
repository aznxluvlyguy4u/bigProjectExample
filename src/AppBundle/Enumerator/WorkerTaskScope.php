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

    //Measurements
    const EXTERIOR = 'EXTERIOR';
    const WEIGHT = 'WEIGHT';
}