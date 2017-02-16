<?php


namespace AppBundle\JsonFormat;


use AppBundle\Util\Validator;

class ValidationResults
{
    const VALID_CODE = 200;
    const ERROR_CODE = 428;
    const VALID_MESSAGE = 'OK';
    const ERROR_MESSAGE = 'INVALID INPUT';
    
    /** @var boolean */
    private $isValid;

    /** @var array */
    private $errors;

    /** @var object */
    private $validResultObject;

    /** @var int */
    private $errorCode;

    /**
     * ValidationResults constructor.
     * @param bool $isValid
     */
    public function __construct($isValid)
    {
        $this->isValid = $isValid;
        $this->errors = [];
        $this->errorCode = self::ERROR_CODE;
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        return $this->isValid;
    }

    /**
     * @param boolean $isValid
     */
    public function setIsValid($isValid)
    {
        $this->isValid = $isValid;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param array $errors
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;
    }

    /**
     * @param string $errorMessage
     */
    public function addError($errorMessage)
    {
        $this->errors[] = $errorMessage;
    }

    /**
     * @return object
     */
    public function getValidResultObject()
    {
        return $this->validResultObject;
    }

    /**
     * @param object $validResultObject
     */
    public function setValidResultObject($validResultObject)
    {
        $this->validResultObject = $validResultObject;
    }

    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param int $errorCode
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;
    }

    /**
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    public function getJsonResponse()
    {
        if($this->isValid()){
            return Validator::createJsonResponse(self::VALID_MESSAGE, self::VALID_CODE);
        } else {
            return Validator::createJsonResponse(self::ERROR_MESSAGE, $this->errorCode, $this->errors);
        }
    }
}