<?php

namespace AppBundle\Validation;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\NullChecker;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

abstract class DeclareNsfoBaseValidator
{
    const ERROR_CODE = 428;
    const ERROR_MESSAGE = 'INVALID INPUT';
    const VALID_CODE = 200;
    const VALID_MESSAGE = 'OK';

    /** @var boolean */
    protected $isInputValid;

    /** @var AnimalRepository */
    protected $animalRepository;

    /** @var ObjectManager */
    protected $manager;

    /** @var Client */
    protected $client;

    /** @var array */
    protected $errors;
    

    public function __construct(ObjectManager $manager, ArrayCollection $content, Client $client)
    {
        $this->manager = $manager;
        $this->animalRepository = $this->manager->getRepository(Animal::class);
        $this->client = $client;
        $this->isInputValid = false;
        $this->errors = array();
    }


    /**
     * @return bool
     */
    public function getIsInputValid()
    {
        return $this->isInputValid;
    }



    /**
     * @return JsonResponse
     */
    public function createJsonResponse()
    {
        if($this->isInputValid){
            return Validator::createJsonResponse(self::VALID_MESSAGE, self::VALID_CODE);
        } else {
            return Validator::createJsonResponse(self::ERROR_MESSAGE, self::ERROR_CODE, $this->errors);
        }
    }
    
    
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