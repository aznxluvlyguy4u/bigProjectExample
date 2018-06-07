<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\MobileDevice;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use Symfony\Component\HttpFoundation\Request;

class MobileService extends ControllerServiceBase
{
    /**
     * @param Request $request
     * @param $messageId
     * @return JsonResponse
     */
    public function logout(Request $request)
    {
        $requestData = RequestUtil::getContentAsArray($request);
        $uuid = $requestData[JsonInputConstant::DEVICE_ID];
        if(!empty($uuid)) {
            $mobileDevice = $this->getManager()->getRepository(MobileDevice::class)->findOneBy(['uuid' => $uuid]);
            if($mobileDevice) {
                $this->getManager()->remove($mobileDevice);
                $this->getManager()->flush();
            }
        }

        return ResultUtil::successResult('ok');
    }

    public function validateRegistrationToken(Request $request)
    {
        $requestData = RequestUtil::getContentAsArray($request);
        $uuid = $requestData[JsonInputConstant::DEVICE_ID];
        $registrationToken = $requestData[JsonInputConstant::REGISTRATION_TOKEN];

        if(!empty($uuid) && !empty($registrationToken))
        {
            $mobileDevice = $this->getManager()->getRepository(MobileDevice::class)->findOneBy(['uuid' => $uuid]);
            if($mobileDevice) {
                if($mobileDevice->getRegistrationToken() != $registrationToken){
                    $mobileDevice->setRegistrationToken($registrationToken);
                    $this->getManager()->persist($mobileDevice);
                    $this->getManager()->flush();
                }
            }
        }

        return ResultUtil::successResult('ok');
    }
}