<?php


namespace AppBundle\Enumerator;


class WorkerAction
{
    const GENERATE_REPORT = 'generate_report';
    const PROCESS_SQS_COMMAND = 'process_sqs_command';
    const UPDATE_ANIMAL_DATA = 'update_animal_data';
}