<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Output\MenuBarOutput;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;

class ComponentService extends ControllerServiceBase
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getMenuBar(Request $request)
    {
        $client = $this->getAccountOwner($request);
        if($client == null) { return ResultUtil::errorResult('Client cannot be null', 428); }

        $output = MenuBarOutput::create($client);
        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAdminMenuBar(Request $request)
    {
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $outputArray = MenuBarOutput::createAdmin($admin);
        return ResultUtil::successResult($outputArray);
    }
}