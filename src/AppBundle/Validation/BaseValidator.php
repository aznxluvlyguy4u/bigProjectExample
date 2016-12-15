<?php

namespace AppBundle\Validation;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class BaseValidator
 * @package AppBundle\Validation
 */
abstract class BaseValidator
{
    const ERROR_CODE = 428;
    const ERROR_MESSAGE = 'INVALID INPUT';
    const VALID_CODE = 200;
    const VALID_MESSAGE = 'OK';

    /** @var boolean */
    protected $isInputValid;

    /** @var ObjectManager */
    protected $manager;

    /** @var array */
    protected $errors;

    /** @var ArrayCollection */
    protected $content;

    public function __construct(ObjectManager $manager, ArrayCollection $content)
    {
        $this->manager = $manager;
        $this->isInputValid = false;
        $this->errors = array();
        $this->content = $content;
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

}