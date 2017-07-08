<?php

namespace AppBundle\Validation;

use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Person;
use AppBundle\Entity\PersonRepository;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\JsonFormat\ValidationResults;
use Doctrine\Common\Collections\Collection;
use AppBundle\Constant\Constant;
use \Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class AdminValidator
 * @package AppBundle\Validation
 */
class AdminValidator
{
    const ERROR_CODE = 401;
    const ERROR_MESSAGE = 'UNAUTHORIZED';
    const VALID_CODE = 200;
    const VALID_MESSAGE = 'ACCESS GRANTED';

    /** @var boolean */
    private $isAccessGranted;

    /** @var string */
    private $accessLevelRequired;

    /**
     * AdminValidator constructor.
     * @param Person $admin
     * @param string $accessLevelRequired
     */
    public function __construct($admin, $accessLevelRequired = AccessLevelType::ADMIN)
    {
        $this->accessLevelRequired = $accessLevelRequired;

        //Validate user
        if(!($admin instanceof Employee)) {
            $this->isAccessGranted = false;
        } else {
            $this->isAccessGranted = self::checkIsAccessGranted($this->accessLevelRequired, $admin->getAccessLevel());
        }
    }

    public function getIsAccessGranted() { return $this->isAccessGranted; }

    /**
     * @return JsonResponse
     */
    public function createJsonErrorResponse()
    {
        if($this->isAccessGranted){
            $message = self::VALID_MESSAGE;
            $code = self::VALID_CODE;
        } else {
            $message = self::ERROR_MESSAGE;
            $code = self::ERROR_CODE;
        }

        $result = array(
            Constant::MESSAGE_NAMESPACE => $message,
            Constant::CODE_NAMESPACE => $code);

        return new JsonResponse($result, $code);
    }

    /**
     * @param string $accessLevelRequired
     * @param string $accessLevelUsed
     * @return boolean
     */
    public static function checkIsAccessGranted($accessLevelRequired, $accessLevelUsed)
    {
        $isAccessGranted = false;

        switch ($accessLevelRequired) {
            case AccessLevelType::DEVELOPER: //can be accessed by...
                if($accessLevelUsed == AccessLevelType::DEVELOPER) { $isAccessGranted = true; }
                break;

            case AccessLevelType::SUPER_ADMIN: //can be accessed by...
                if($accessLevelUsed == AccessLevelType::DEVELOPER) { $isAccessGranted = true; break;}
                if($accessLevelUsed == AccessLevelType::SUPER_ADMIN) { $isAccessGranted = true; }
                break;

            default: //includes accessLevel ADMIN can be accessed by...
                if($accessLevelUsed == AccessLevelType::DEVELOPER) { $isAccessGranted = true; break;} 
                if($accessLevelUsed == AccessLevelType::SUPER_ADMIN) { $isAccessGranted = true; break;}
                if($accessLevelUsed == AccessLevelType::ADMIN) { $isAccessGranted = true; }
                break;
        }

        return $isAccessGranted;
    }


    /**
     * @param $admin
     * @param $accessLevelRequired
     * @return boolean
     */
    public static function isAdmin($admin, $accessLevelRequired)
    {
        if($admin instanceof Employee) {
            return self::checkIsAccessGranted($accessLevelRequired, $admin->getAccessLevel());
        }

        return false;
    }
    
    
    /**
     * @param $admin
     * @param $accessLevelRequired
     * @return ValidationResults
     */
    public static function validate($admin, $accessLevelRequired)
    {
        $validationResults = new ValidationResults(self::isAdmin($admin, $accessLevelRequired));
        $validationResults->setErrorCode(self::ERROR_CODE);

        if(!$validationResults->isValid()) {
            $validationResults->addError('UNAUTHORIZED');
        }

        return $validationResults;
    }


    /**
     * @return string
     */
    public static function getStandardErrorResponse()
    {
        $code = self::ERROR_CODE;
        $message = 'UNAUTHORIZED';

        return new JsonResponse(['code'=>$code, "message" => $message], $code);
    }
}