<?php


namespace AppBundle\Util;


use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\Litter;
use AppBundle\Worker\Task\WorkerMessageBodyLitter;

class WorkerTaskUtil
{
    /**
     * @param $birthRequestMessages
     * @return WorkerMessageBodyLitter
     */
    public static function createResultTableMessageBodyByBirthRequests($birthRequestMessages)
    {
        if(!is_array($birthRequestMessages)) { return null; }
        if(count($birthRequestMessages) == 0) { return null; }

        /** @var DeclareBirth $firstBirth */
        $firstBirth = $birthRequestMessages[0];
        self::createResultTableMessageBodyByLitter($firstBirth->getLitter());
    }


    public static function createResultTableMessageBodyByLitter(Litter $litter)
    {
        $fatherId = null;
        $motherId = null;
        $childrenIds = [];

        if(!($litter instanceof Litter)) { return null; }


        $father = $litter->getAnimalFather();
        if($father) {
            $fatherId = $father->getId();
        }

        $mother = $litter->getAnimalMother();
        if($mother) {
            $motherId = $mother->getId();
        }

        $children = $litter->getChildren();
        foreach ($children as $child) {
            if($child instanceof Animal) {
                $childId = $child->getId();
                if($childId != null) {
                    $childrenIds[] = $childId;
                }
            }
        }

        return self::createLitterResultTableBody($motherId, $fatherId, $childrenIds);
    }


    /**
     * @param int $motherId
     * @param int $fatherId
     * @param array $childrenIds
     * @return WorkerMessageBodyLitter
     */
    public static function createLitterResultTableBody($motherId, $fatherId, array $childrenIds)
    {
        if(!is_int($motherId) && !is_int($fatherId) && count($childrenIds) == 0) { return null; }

        $messageBody = new WorkerMessageBodyLitter();
        $messageBody->setFatherId($fatherId);
        $messageBody->setMotherId($motherId);
        $messageBody->setChildrenIds($childrenIds);
        $messageBody->setOnlyProcessBlankRecords(false);

        return $messageBody;
    }
}