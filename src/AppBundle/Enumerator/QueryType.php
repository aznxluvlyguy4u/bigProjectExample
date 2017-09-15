<?php

namespace AppBundle\Enumerator;

use AppBundle\Traits\EnumInfo;


/**
 * Class QueryType
 */
class QueryType
{
    use EnumInfo;

    const BASE_INSERT = 'BASE_INSERT';
    const INSERT = 'INSERT';
    const UPDATE = 'UPDATE';
    const UPDATE_BASE = 'UPDATE_BASE';
    const UPDATE_END = 'UPDATE_END';
    const DELETE = 'DELETE';
    const SELECT = 'SELECT';

}