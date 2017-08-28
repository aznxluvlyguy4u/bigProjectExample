<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\AdminProfileAPIControllerInterface;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\FormInput\AdminProfile;
use AppBundle\Output\AdminOverviewOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\AdminActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\EditAdminProfileValidator;
use AppBundle\Validation\PasswordValidator;
use Symfony\Component\HttpFoundation\Request;

class AdminProfileService extends AuthServiceBase implements AdminProfileAPIControllerInterface
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAdminProfile(Request $request)
    {
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $outputArray = AdminOverviewOutput::createAdminOverview($admin);
        return ResultUtil::successResult($outputArray);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function editAdminProfile(Request $request)
    {
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $content = RequestUtil::getContentAsArray($request);

        //Validate input
        $inputValidator = new EditAdminProfileValidator($this->getManager(), $content, $admin);
        if (!$inputValidator->getIsValid()) {
            return $inputValidator->createJsonResponse();
        }

        //If password is changed: validate and encrypt it
        $newPassword = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::NEW_PASSWORD, $content);
        $passwordChangeLog = null;
        if($newPassword != null) {
            $newPassword = base64_decode($newPassword);

            //Validate password format
            $passwordValidator = new PasswordValidator($newPassword);
            if(!$passwordValidator->getIsPasswordValid()) {
                return $passwordValidator->createJsonErrorResponse();
            }
            $encodedNewPassword = $this->encoder->encodePassword($admin, $newPassword);
            $content->set(JsonInputConstant::NEW_PASSWORD, $encodedNewPassword);
            $passwordChangeLog = AdminActionLogWriter::passwordChangeAdminInProfile($this->getManager(), $admin);
        }

        $valuesLog = AdminActionLogWriter::editOwnAdminProfile($this->getManager(), $admin, $content);

        //Persist updated changes and return the updated values
        $client = AdminProfile::update($admin, $content);
        $this->getManager()->persist($admin);
        $this->getManager()->flush();

        $outputArray = AdminOverviewOutput::createAdminOverview($admin);

        ActionLogWriter::completeActionLog($this->getManager(), $valuesLog);
        if ($passwordChangeLog) {
            ActionLogWriter::completeActionLog($this->getManager(), $passwordChangeLog);
        }

        return ResultUtil::successResult($outputArray);
    }
}