<?php

namespace AppBundle\Validation;

use AppBundle\Constant\Constant;
use \Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class PasswordValidator
 */
class PasswordValidator
{
    const DEFAULT_PASSWORD_MIN_LENGTH = 6;
    const RESPONSE_INVALID_INPUT = "PASSWORD IS INVALID";
    const ERROR_CODE = 428;

    private $isPasswordValid;
    private $passwordMinLength;
    private $password;
    private $errors;

    /**
     * PasswordValidator constructor.
     * @param string $password
     * @param int $passwordMinLength
     */
    public function __construct($password, $passwordMinLength = PasswordValidator::DEFAULT_PASSWORD_MIN_LENGTH)
    {
        //Initialize variables
        $this->errors = array();

        //Set given values
        $this->password = $password;
        $this->passwordMinLength = $passwordMinLength;

        //Validate password
        $this->validate();
    }

    public function getIsPasswordValid() { return $this->isPasswordValid; }

    /**
     *
     */
    private function validate()
    {
        //Initialize default validity
        $this->isPasswordValid = true;

        //Conditions that may invalidate a password
        $this->verifyPasswordLength();
    }

    /**
     *
     */
    private function verifyPasswordLength()
    {
        if(strlen($this->password) < $this->passwordMinLength)
        {
            $this->isPasswordValid = false;
            $this->errors[] = 'PASSWORD IS SHORTER THAN ' . $this->passwordMinLength . ' CHARACTERS';
        }
    }

    public function createJsonErrorResponse()
    {
        $result = array(
               Constant::CODE_NAMESPACE => PasswordValidator::ERROR_CODE,
            Constant::MESSAGE_NAMESPACE => PasswordValidator::RESPONSE_INVALID_INPUT,
             Constant::ERRORS_NAMESPACE => $this->errors);

        return new JsonResponse($result, PasswordValidator::ERROR_CODE);
    }

}