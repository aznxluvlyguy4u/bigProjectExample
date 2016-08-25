<?php

namespace AppBundle\Validation;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\DeclareWeight;
use AppBundle\Entity\Location;
use AppBundle\Entity\Mate;
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

    const MESSAGE_ID_ERROR     = 'MESSAGE ID: NO MESSAGE FOUND FOR GIVEN MESSAGE ID AND CLIENT';
    const MESSAGE_OVERWRITTEN  = 'MESSAGE ID: MESSAGE IS ALREADY OVERWRITTEN';

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
     * @param string $messageId
     * @return DeclareNsfoBase|Mate|DeclareWeight|boolean
     */
    protected function isNonRevokedNsfoDeclarationOfClient($messageId)
    {
        return Validator::isNonRevokedNsfoDeclarationOfClient($this->manager, $this->client, $messageId);
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