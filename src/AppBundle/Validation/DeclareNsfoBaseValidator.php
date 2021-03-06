<?php

namespace AppBundle\Validation;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\DeclareWeight;
use AppBundle\Entity\Mate;
use AppBundle\Entity\Person;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

abstract class DeclareNsfoBaseValidator extends BaseValidator
{
    const MESSAGE_ID_ERROR     = 'MESSAGE ID: NO MESSAGE FOUND FOR GIVEN MESSAGE ID AND CLIENT';
    const MESSAGE_OVERWRITTEN  = 'MESSAGE ID: MESSAGE IS ALREADY OVERWRITTEN';

    /** @var AnimalRepository */
    protected $animalRepository;
    
    /** @var Client */
    protected $client;

    /** @var Person */
    protected $loggedInUser;

    public function __construct(ObjectManager $manager, ArrayCollection $content, Client $client,
                                Person $loggedInUser = null)
    {
        parent::__construct($manager, $content);
        $this->animalRepository = $this->manager->getRepository(Animal::class);
        $this->client = $client;
        $this->loggedInUser = $loggedInUser;
    }
    
    
    /**
     * Returns Mate if true.
     *
     * @param string $messageId
     * @return DeclareNsfoBase|Mate|DeclareWeight|boolean
     */
    protected function isNonRevokedNsfoDeclarationOfClient($messageId)
    {
        return Validator::isNonRevokedNsfoDeclarationOfClient($this->manager, $this->client, $messageId, $this->loggedInUser);
    }


    /**
     * @param DeclareWeight|Mate $nsfoDeclaration
     * @return bool
     */
    protected function validateNsfoDeclarationIsNotAlreadyOverwritten($nsfoDeclaration)
    {
        if($nsfoDeclaration->getIsOverwrittenVersion()) {
            $this->errors[] = self::MESSAGE_OVERWRITTEN;
            return false;
        } else {
            return true;
        }
    }
}