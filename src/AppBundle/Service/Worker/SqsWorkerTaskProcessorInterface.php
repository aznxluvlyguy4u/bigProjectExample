<?php


namespace AppBundle\Service\Worker;


use Aws\Result;

interface SqsWorkerTaskProcessorInterface
{
    function process(Result $queueMessage);
}