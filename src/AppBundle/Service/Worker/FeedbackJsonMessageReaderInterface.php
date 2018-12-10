<?php


namespace AppBundle\Service\Worker;


use AppBundle\Entity\AnimalRelocation;
use AppBundle\Entity\HealthCheckTask;

interface FeedbackJsonMessageReaderInterface
{
    function readHealthCheckTask(string $queueMessageBody): ?HealthCheckTask;
    function readAnimalRelocation(string $queueMessageBody): ?AnimalRelocation;
}