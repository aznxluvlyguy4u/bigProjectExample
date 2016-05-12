<?php

namespace AppBundle\Service;

use AppBundle\Enumerator\RequestStateType;
use Doctrine\ORM\EntityManager;

class EntitySetter extends EntityGetter
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }


    public function setRequestStateToRevoked($messageId)
    {
        $messageObject = $this->getRequestMessageByMessageId($messageId);
        $messageObject->setRequestState(RequestStateType::REVOKED);

        return $messageObject;
    }
}