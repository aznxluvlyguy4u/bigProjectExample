<?php

namespace AppBundle\Validation;

use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Person;
use AppBundle\Output\AccessLevelOverviewOutput;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Constant\Constant;
use Doctrine\ORM\EntityManager;
use \Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class CreateAdminValidator
 * @package AppBundle\Validation
 */
class CreateAdminValidator
{
    const RESPONSE_INVALID_INPUT_FIRST_NAME = "FIRST NAME CANNOT BE EMPTY AND SHOULD ONLY CONTAIN ALPHANUMERIC CHARACTERS";
    const RESPONSE_INVALID_INPUT_LAST_NAME = "LAST NAME CANNOT BE EMPTY AND SHOULD ONLY CONTAIN ALPHANUMERIC CHARACTERS";
    const RESPONSE_INVALID_INPUT_EMAIL = "EMAIL ADDRESS FORMAT IS INCORRECT";
    const RESPONSE_INPUT_EMAIL_IN_USE = "EMAIL ADDRESS IS ALREADY IN USE";
    const RESPONSE_INVALID_ACCESS_LEVEL = "ACCESS LEVEL IS NOT VALID";

    const EMPTY_FIRST_NAME = 'EMPTY FIRST NAME';
    const EMPTY_LAST_NAME = 'EMPTY LAST NAME';
    const EMPTY_EMAIL_ADDRESS = 'EMPTY EMAIL ADDRESS';
    const EMPTY_ACCESS_LEVEL_TYPE = 'EMPTY ACCESS LEVEL TYPE';

    const ERROR_CODE = 428;
    const OVERALL_ERROR_MESSAGE = 'INPUT IS INVALID';
    const VALID_CODE = 200;
    const VALID_MESSAGE = 'INPUT IS VALID';

    /** @var  boolean */
    private $isValid;

    /** @var array */
    private $errors;

    /** @var EntityManager */
    private $em;

    /**
     * PasswordValidator constructor.
     * @param array $admins
     */
    public function __construct(EntityManager $em, $admins)
    {
        //Initialize variables
        $this->errors = array();
        $this->isValid = true;

        //Set given values
        $this->em = $em;

        //Validate
        $this->validate($admins);

    }

    public function getIsValid() { return $this->isValid; }

    /**
     * @param array $admins
     */
    private function validate($admins)
    {
        foreach ($admins as $admin) {

            $firstName = Utils::getNullCheckedArrayValue(JsonInputConstant::FIRST_NAME, $admin);
            $this->validateFirstName($firstName);

            $lastName = Utils::getNullCheckedArrayValue(JsonInputConstant::LAST_NAME, $admin);
            $this->validateLastName($lastName);

            $emailAddress = Utils::getNullCheckedArrayValue(JsonInputConstant::EMAIL_ADDRESS, $admin);
            $this->validateEmailAddress($emailAddress);
            
            $accessLevel = Utils::getNullCheckedArrayValue(JsonInputConstant::ACCESS_LEVEL, $admin);
            $this->validateAccessLevelType($accessLevel);
        }
    }
    
    /**
     * @param string $firstName
     */
    private function validateFirstName($firstName)
    {
        if($firstName == null || $firstName == "" || $firstName == " ") {
            $this->isValid = false;
            $this->errors[self::EMPTY_FIRST_NAME] = self::RESPONSE_INVALID_INPUT_FIRST_NAME;

        } elseif(!ctype_alnum($firstName)) {
            $this->isValid = false;
            $this->errors[$firstName] = self::RESPONSE_INVALID_INPUT_FIRST_NAME;
        }
    }

    /**
     * @param string $lastName
     */
    private function validateLastName($lastName)
    {
        if($lastName == null || $lastName == "" || $lastName == " ") {
            $this->isValid = false;
            $this->errors[self::EMPTY_LAST_NAME] = self::RESPONSE_INVALID_INPUT_LAST_NAME;

        } elseif(!ctype_alnum($lastName)) {
            $this->isValid = false;
            $this->errors[$lastName] = self::RESPONSE_INVALID_INPUT_LAST_NAME;
        }
    }

    /**
     * @param string $emailAddress
     */
    private function validateEmailAddress($emailAddress)
    {
        if($emailAddress == null || $emailAddress == "" || $emailAddress == " ") {
            $this->isValid = false;
            $this->errors[self::EMPTY_EMAIL_ADDRESS] = self::RESPONSE_INVALID_INPUT_EMAIL;

        } elseif(!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            $this->isValid = false;
            $this->errors[$emailAddress] = self::RESPONSE_INVALID_INPUT_EMAIL;

        } else {
            $persons = $this->em->getRepository(Person::class)->findBy(['emailAddress' => $emailAddress]);
            if(sizeof($persons) > 0) {
                $this->isValid = false;
                $this->errors[$emailAddress] = self::RESPONSE_INPUT_EMAIL_IN_USE;
            }
        }
    }

    /**
     * @param string $accessLevel
     */
    private function validateAccessLevelType($accessLevel)
    {
        $accessLevelTypes = AccessLevelOverviewOutput::getTypes();

        if($accessLevel == null || $accessLevel == "" || $accessLevel == " ") {
            $this->isValid = false;
            $this->errors[self::EMPTY_ACCESS_LEVEL_TYPE] = self::RESPONSE_INVALID_ACCESS_LEVEL;

        } elseif(!in_array($accessLevel, $accessLevelTypes)) {
            $this->isValid = false;
            $this->errors[$accessLevel] = self::RESPONSE_INVALID_ACCESS_LEVEL;
        }
    }

    /**
     * @return JsonResponse
     */
    public function createJsonResponse()
    {
        if($this->isValid) {
            $code = self::VALID_CODE;
            $result = array(
                Constant::CODE_NAMESPACE => $code,
                Constant::MESSAGE_NAMESPACE => self::VALID_MESSAGE,
                Constant::ERRORS_NAMESPACE => $this->errors); //returning an empty array

        } else {
            $code = self::ERROR_CODE;
            $result = array(
                Constant::CODE_NAMESPACE => $code,
                Constant::MESSAGE_NAMESPACE => self::OVERALL_ERROR_MESSAGE,
                Constant::ERRORS_NAMESPACE => $this->errors);
        }

        return new JsonResponse($result, $code);
    }

    /**
     * @return ArrayCollection
     */
    public static function getInputFields()
    {
        $inputFields = new ArrayCollection();

        $inputFields->add(JsonInputConstant::FIRST_NAME);
        $inputFields->add(JsonInputConstant::LAST_NAME);
        $inputFields->add(JsonInputConstant::EMAIL_ADDRESS);
        $inputFields->add(JsonInputConstant::ACCESS_LEVEL);

        return $inputFields;
    }

}