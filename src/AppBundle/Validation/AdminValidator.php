<?php

namespace AppBundle\Validation;

use AppBundle\Component\HttpFoundation\JsonResponse as AppBundleJsonResponse;
use AppBundle\Entity\Employee;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\JsonFormat\ValidationResults;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

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


    /**
     * @param $admin
     * @param $accessLevelRequired
     * @param $throwExceptionIfUnauthorized
     * @return boolean
     */
    public static function isAdmin($admin, $accessLevelRequired, $throwExceptionIfUnauthorized = false)
    {
        $isAuthorized = $admin instanceof Employee
            && self::checkIsAccessGranted($accessLevelRequired, $admin->getAccessLevel());

        if (!$isAuthorized && $throwExceptionIfUnauthorized) {
            throw Validator::unauthorizedException();
        }

        return $isAuthorized;
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
     * @return ValidationResults
     */
    public static function validate($admin, $accessLevelRequired)
    {
        $validationResults = new ValidationResults(self::isAdmin($admin, $accessLevelRequired));
        $validationResults->setErrorCode(self::ERROR_CODE);

        if(!$validationResults->isValid()) {
            $validationResults->addError(self::ERROR_MESSAGE);
        }

        return $validationResults;
    }


    /**
     * @return JsonResponse|AppBundleJsonResponse
     */
    public static function getStandardErrorResponse()
    {
        return ResultUtil::errorResult(self::ERROR_MESSAGE, self::ERROR_CODE);
    }


    /**
     * @return UnauthorizedHttpException
     */
    public static function standardException(): UnauthorizedHttpException
    {
        return Validator::unauthorizedException();
    }
}