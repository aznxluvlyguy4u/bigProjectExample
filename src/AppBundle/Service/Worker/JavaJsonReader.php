<?php


namespace AppBundle\Service\Worker;


use AppBundle\Entity\AnimalRelocation;
use AppBundle\Entity\HealthCheckTask;
use AppBundle\Entity\Location;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Exception\Sqs\SqsMessageInvalidBodyException;
use AppBundle\Service\BaseSerializer;
use AppBundle\Util\ArrayUtil;
use Doctrine\ORM\EntityManagerInterface;

class JavaJsonReader implements FeedbackJsonMessageReaderInterface
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var BaseSerializer */
    private $serializer;

    public function __construct(EntityManagerInterface $em, BaseSerializer $serializer)
    {
        $this->em = $em;
        $this->serializer = $serializer;
    }

    /**
     * @return EntityManagerInterface
     */
    private function getManager(): EntityManagerInterface
    {
        return $this->em;
    }

    /**
     * @return BaseSerializer
     */
    private function getSerializer(): BaseSerializer
    {
        return $this->serializer;
    }


    /**
     * @param string $queueMessageBody
     * @return HealthCheckTask|null
     * @throws SqsMessageInvalidBodyException
     */
    function readHealthCheckTask(string $queueMessageBody): ?HealthCheckTask
    {
        $taskArray = $this->getValidatedDecodedJson($queueMessageBody);

        // Extract foreign keys first

        $destinationLocationId = ArrayUtil::getNestedValue(['destination_location', 'id'],$taskArray);
        $retrieveAnimalsId = ArrayUtil::getNestedValue(['retrieve_animals', 'id'], $taskArray);

        // Remove invalid keys

        $taskArray = ArrayUtil::removeKeys($taskArray, [
           '@id',
            'destination_location',
            'retrieve_animals'
        ]);

        // Reformat dates

        ArrayUtil::reformatJsonDateValue('arrival_date', $taskArray);
        ArrayUtil::reformatJsonDateValue('sync_date', $taskArray);

        /** @var HealthCheckTask $task */
        $task = $this->getSerializer()->denormalizeToObject($taskArray, HealthCheckTask::class, false);

        if (!$task) {
            throw new SqsMessageInvalidBodyException('HealthCheckTask is empty');
        }

        // Set objects

        $destination = $this->getManager()->getRepository(Location::class)->find($destinationLocationId);
        $task->setDestinationLocation($destination);

        $retrieveAnimals = $this->getManager()->getRepository(RetrieveAnimals::class)->find($retrieveAnimalsId);
        $task->setRetrieveAnimals($retrieveAnimals);

        return $task;
    }


    /**
     * @param string $queueMessageBody
     * @return AnimalRelocation|null
     * @throws SqsMessageInvalidBodyException
     */
    function readAnimalRelocation(string $queueMessageBody): ?AnimalRelocation
    {
        $animalRelocationArray = $this->getValidatedDecodedJson($queueMessageBody);

        // TODO: Implement readAnimalRelocation() method.

        /** @var AnimalRelocation $task */
        $animalRelocation = $this->getSerializer()->denormalizeToObject($animalRelocationArray, HealthCheckTask::class, false);

        if (!$animalRelocation) {
            throw new SqsMessageInvalidBodyException('AnimalRelocation is empty');
        }

        return $animalRelocation;
    }


    /**
     * @param string $queueMessageBody
     * @return array|null
     * @throws SqsMessageInvalidBodyException
     */
    private function getValidatedDecodedJson(string $queueMessageBody): ?array
    {
        $array = json_decode($queueMessageBody, true);
        if (!$array) {
            throw new SqsMessageInvalidBodyException('Not a valid json');
        }
        return $array;
    }






}