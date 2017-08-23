<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\AdminProfileAPIControllerInterface;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\FormInput\AdminProfile;
use AppBundle\Output\AdminOverviewOutput;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\EditAdminProfileValidator;
use AppBundle\Validation\PasswordValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AdminProfileService extends ControllerServiceBase implements AdminProfileAPIControllerInterface
{

    /** @var UserPasswordEncoderInterface */
    private $encoder;

    public function __construct(EntityManagerInterface $em, IRSerializer $serializer, CacheService $cacheService,
                                UserService $userService, UserPasswordEncoderInterface $encoder)
    {
        parent::__construct($em, $serializer, $cacheService, $userService);

        $this->encoder = $encoder;
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAdminProfile(Request $request)
    {
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::SUPER_ADMIN)) {
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
        if (!AdminValidator::isAdmin($admin, AccessLevelType::SUPER_ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $content = RequestUtil::getContentAsArray($request);

        //Validate input
        $inputValidator = new EditAdminProfileValidator($this->em, $content, $admin);
        if (!$inputValidator->getIsValid()) {
            return $inputValidator->createJsonResponse();
        }

        //If password is changed: validate and encrypt it
        $newPassword = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::NEW_PASSWORD, $content);
        if($newPassword != null) {
            $newPassword = base64_decode($newPassword);

            //Validate password format
            $passwordValidator = new PasswordValidator($newPassword);
            if(!$passwordValidator->getIsPasswordValid()) {
                return $passwordValidator->createJsonErrorResponse();
            }
            $encodedNewPassword = $this->encoder->encodePassword($admin, $newPassword);
            $content->set(JsonInputConstant::NEW_PASSWORD, $encodedNewPassword);
        }

        //Persist updated changes and return the updated values
        $client = AdminProfile::update($admin, $content);
        $this->em->persist($admin);
        $this->em->flush();

        $outputArray = AdminOverviewOutput::createAdminOverview($admin);
        return ResultUtil::successResult($outputArray);
    }
}