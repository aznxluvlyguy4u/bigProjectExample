<?php

namespace AppBundle\Component;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\Location;
use AppBundle\Entity\Mate;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\RequestStateType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * The base for non I&R messages/declarations
 * 
 * Class NsfoBaseBuilder
 * @package AppBundle\Component
 */
class NsfoBaseBuilder
{
    /**
     * @param Client $client
     * @param Person $loggedInUser
     * @param Location $location
     * @param DeclareNsfoBase $nsfoDeclaration
     * @return DeclareNsfoBase|Mate
     */
    protected static function postBase(Client $client, Person $loggedInUser,
                                       Location $location, DeclareNsfoBase $nsfoDeclaration)
    {
        $nsfoDeclaration->setRequestState(RequestStateType::FINISHED);
        $nsfoDeclaration->setRelationNumberKeeper($client->getRelationNumberKeeper());
        $nsfoDeclaration->setUbn($location->getUbn());
        $nsfoDeclaration->setActionBy($loggedInUser);

        if($nsfoDeclaration->getMessageId() == null) {
            $nsfoDeclaration->setMessageId(MessageBuilderBase::getNewRequestId());
        }

        if($nsfoDeclaration->getLogDate() == null) {
            $nsfoDeclaration->setLogDate(new \DateTime());
        }

        $nsfoDeclaration->setIsOverwrittenVersion(false);
        $nsfoDeclaration->setIsHidden(false);
        
        return $nsfoDeclaration;
    }
}