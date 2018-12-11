<?php


namespace AppBundle\Service\Worker;


use AppBundle\Enumerator\SqsCommandType;
use Aws\Result;

class SyncAnimalRelocationProcessor extends SqsWorkerTaskProcessorBase implements SqsWorkerTaskProcessorInterface
{
    function process(Result $queueMessage)
    {
        $this->logStartMessage(SqsCommandType::SYNC_ANIMAL_RELOCATION);
        // TODO: Implement process() method.
    }


}