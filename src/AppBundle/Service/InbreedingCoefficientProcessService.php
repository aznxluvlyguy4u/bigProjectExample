<?php


namespace AppBundle\Service;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Service\InbreedingCoefficient\InbreedingCoefficientAllAnimalsUpdaterService;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;

class InbreedingCoefficientProcessService
{
    /** @var UserService */
    private $userService;

    /** @var InbreedingCoefficientAllAnimalsUpdaterService */
    private $allAnimalsUpdaterService;

    /**
     * InbreedingCoefficientProcessService constructor.
     *
     * @param UserService $userService
     * @param InbreedingCoefficientAllAnimalsUpdaterService $allAnimalsUpdaterService
     */
    public function __construct(
        UserService $userService,
        InbreedingCoefficientAllAnimalsUpdaterService $allAnimalsUpdaterService
    )
    {
        $this->userService = $userService;
        $this->allAnimalsUpdaterService = $allAnimalsUpdaterService;
    }

    /**
     * @return JsonResponse
     */
    public function startGenerationForAllAnimals()
    {
        $this->validateIsSuperUser();
        $this->allAnimalsUpdaterService->start();
        return ResultUtil::successResult('ok');
    }


    /**
     * @return JsonResponse
     */
    public function startRegenerationForAllAnimals()
    {
        $this->validateIsSuperUser();
        $this->allAnimalsUpdaterService->start(true);
        return ResultUtil::successResult('ok');
    }


    /**
     * @return JsonResponse
     */
    public function cancelAllAnimalsProcess()
    {
        $this->validateIsSuperUser();
        $isNewCancellation = $this->allAnimalsUpdaterService->cancel();
        return $isNewCancellation ? ResultUtil::successResult('ok') : ResultUtil::noContent();
    }


    private function validateIsSuperUser()
    {
        AdminValidator::isAdmin(
            $this->userService->getUser(),
            AccessLevelType::SUPER_ADMIN,
            true
        );
    }
}
