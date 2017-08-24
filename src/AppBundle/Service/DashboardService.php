<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\DeclareBase;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Output\AdminDashboardOutput;
use AppBundle\Output\DashboardOutput;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;

class DashboardService extends ControllerServiceBase
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getDashBoard(Request $request)
    {
        $client = $this->getAccountOwner($request);
        $location = $this->getSelectedLocation($request);

        if($client == null) { return ResultUtil::errorResult('Client cannot be null', 428); }
        if($location == null) { return ResultUtil::errorResult('Location cannot be null', 428); }

        $errorMessageForDateIsNull = "";

        $declarationLogDate = $this->getManager()->getRepository(DeclareBase::class)->getLatestLogDatesForDashboardDeclarationsPerLocation($location, $errorMessageForDateIsNull);

        $outputArray = DashboardOutput::create($this->getManager(), $client, $declarationLogDate, $location);

        return ResultUtil::successResult($outputArray);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAdminDashBoard(Request $request)
    {
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $outputArray = AdminDashboardOutput::createAdminDashboard($this->getManager());

        return ResultUtil::successResult($outputArray);
    }
}