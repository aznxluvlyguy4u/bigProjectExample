<?php


namespace AppBundle\Enumerator;

use AppBundle\Traits\EnumInfo;

/**
 * Class ProcessType
 *
 * The names can only contain letters and numbers. No dashes, spaces and underscores.
 */
class ProcessType
{
    use EnumInfo;

    const SQS_FEEDBACK_WORKER = 'SqsFeedbackWorker';
    const SQS_RAW_EXTERNAL_WORKER = 'SqsRawExternalWorker';
    const SQS_RAW_INTERNAL_WORKER = 'SqsRawInternalWorker';
}