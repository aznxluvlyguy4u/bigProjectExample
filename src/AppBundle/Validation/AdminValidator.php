<?php

namespace AppBundle\Validation;

use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Person;
use AppBundle\Entity\PersonRepository;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\JsonFormat\ValidationResults;
use AppBundle\Output\AccessLevelOverviewOutput;
use AppBundle\Util\ResultUtil;
use Doctrine\Common\Collections\Collection;
use AppBundle\Constant\Constant;
use \Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\HttpFoundation\JsonResponse as AppBundleJsonResponse;

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
}