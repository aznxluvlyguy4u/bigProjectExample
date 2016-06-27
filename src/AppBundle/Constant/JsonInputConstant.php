<?php

namespace AppBundle\Constant;


class JsonInputConstant
{
    const WORK_NUMBER = 'work_number';
    const IS_ALIVE = 'is_alive';

    const REQUEST_ID = 'request_id';

    //Weight measurements
    const DATE_OF_MEASUREMENT = 'date_of_measurement';
    const WEIGHT_MEASUREMENTS = 'weight_measurements';
    const WEIGHT = 'weight';

    //Arrival & Import
    const IS_IMPORT_ANIMAL = 'is_import_animal';
    const UBN_PREVIOUS_OWNER = 'ubn_previous_owner';
    const ARRIVAL_DATE = 'arrival_date';
    const IS_ARRIVED_FROM_OTHER_NSFO_CLIENT = 'is_arrived_from_other_nsfo_client';

    //Locations
    const PROVINCES = 'provinces';
    const CODES = 'codes';

    //Loss
    const UBN_DESTRUCTOR = "ubn_destructor";
}