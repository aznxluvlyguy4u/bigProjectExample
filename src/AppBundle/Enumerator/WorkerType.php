<?php


namespace AppBundle\Enumerator;


class WorkerType
{
    const REPORT = 1;
    const SQS_COMMAND = 2;
    const UPDATE_ANIMAL_DATA = 3;
}