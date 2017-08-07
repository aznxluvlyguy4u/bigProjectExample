<?php


namespace AppBundle\Util;


use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareBirthResponse;
use AppBundle\Entity\Litter;
use AppBundle\Enumerator\WorkerTaskScope;
use AppBundle\Service\IRSerializer;
use AppBundle\Worker\Task\WorkerMessageBodyLitter;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class WorkerTaskUtil
{
    /**
     * @param Litter $litter
     * @return WorkerMessageBodyLitter|null
     */
    public static function createResultTableMessageBodyForBirthRevoke(Litter $litter)
    {
        $messageBody = self::createResultTableMessageBodyByLitter($litter);
        if($messageBody) {
            $messageBody->setScope(WorkerTaskScope::BIRTH_REVOKE);
        }
        return $messageBody;
    }
    
    
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
        $messageBody = self::createResultTableMessageBodyByLitter($firstBirth->getLitter());
        if($messageBody) {
            $messageBody->setScope(WorkerTaskScope::BIRTH);
        }
        return $messageBody;
    }


    /**
     * @param Litter $litter
     * @return WorkerMessageBodyLitter|null
     */
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
        if(!is_int($motherId) && !is_int($fatherId)) { return null; }

        $messageBody = new WorkerMessageBodyLitter();
        $messageBody->setFatherId($fatherId);
        $messageBody->setMotherId($motherId);
        $messageBody->setChildrenIds($childrenIds);
        $messageBody->setOnlyProcessBlankRecords(false);

        return $messageBody;
    }



    /**
     * @param SymfonyRequest $request
     * @return DeclareBirthResponse
     */
    public static function deserializeMessageToDeclareBirthResponse(SymfonyRequest $request, IRSerializer $irSerializer)
    {
        $post = json_decode($request->getContent(), true);
        $post['log_date'] = WorkerTaskUtil::convertJavaEpochToDate($post['log_date']);
        $post['date_of_birth'] = WorkerTaskUtil::convertJavaEpochToDate($post['date_of_birth']);
        /** @var DeclareBirthResponse $declareBirthResponse */
        return $irSerializer->deserializeToObject(json_encode($post), 'DeclareBirthResponse');
    }



    /**
     * @param $dateString
     * @return string
     */
    public static function convertJavaEpochToDate($dateString)
    {
        $epoch = round(intval($dateString)/1000);
        $dt = new \DateTime("@$epoch");
        return $dt->format('Y-m-d\\TH:i:sO');
    }
}