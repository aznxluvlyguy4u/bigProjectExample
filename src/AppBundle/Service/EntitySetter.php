<?php

namespace AppBundle\Component;


use AppBundle\Enumerator\RequestStateType;
use AppBundle\Service\EntityGetter;
use Doctrine\ORM\EntityManager;

class EntitySetter extends EntityGetter
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }


    function setRequestStateToRevoked($messageId)
    {
        $messageObject = $this->getRequestMessageByMessageId($messageId);
        $messageObject->setRequestState(RequestStateType::REVOKED);

        return $messageObject;
    }
}