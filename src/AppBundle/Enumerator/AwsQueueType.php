<?php


namespace AppBundle\Enumerator;


class AwsQueueType
{
    /*
     * Queues for the PHP workers that process the RVO messages.
     */
    const EXTERNAL_RAW = 'ext-raw';
    const INTERNAL_RAW = 'int-raw';

    /*
     * Queues for the JAVA workers that process the RVO messages.
     */
    const EXTERNAL = 'ext';
    const INTERNAL = 'int';

    /*
     * Queue for the PHP side to process feedback from the JAVA side.
     */
    const FEEDBACK = 'feedback';

    /*
     * Queues for the MiXBLUP processes.
     */
    const MIXBLUP_INPUT = 'mixblup_input';
    const MIXBLUP_OUTPUT = 'mixblup_output';
}