<?php

namespace AppBundle\Validation;


use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Persistence\ObjectManager;

class DeclareNsfoBaseValidator
{
    /**
     * Returns Mate if true.
     *
     * @param ObjectManager $manager
     * @param Client $client
     * @param string $messageId
     * @return DeclareNsfoBase|boolean
     */
    public static function isNonRevokedNsfoDeclarationOfClient(ObjectManager $manager, $client, $messageId)
    {
        /** @var DeclareNsfoBase $declaration */
        $declaration = $manager->getRepository(DeclareNsfoBase::class)->findOneByMessageId($messageId);

        //null check
        if(!($declaration instanceof DeclareNsfoBase) || $messageId == null) { return false; }

        //Revoke check, to prevent data loss by incorrect data
        if($declaration->getRequestState() == RequestStateType::REVOKED) { return false; }

        /** @var Location $location */
        $location = $manager->getRepository(Location::class)->findOneByUbn($declaration->getUbn());

        $owner = NullChecker::getOwnerOfLocation($location);

        if($owner instanceof Client && $client instanceof Client) {
            /** @var Client $owner */
            if($owner->getId() == $client->getId()) {
                return $declaration;
            }
        }

        return false;
    }
}