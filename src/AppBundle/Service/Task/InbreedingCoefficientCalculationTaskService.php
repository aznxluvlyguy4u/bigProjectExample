<?php


namespace AppBundle\Service\Task;

use AppBundle\Service\InbreedingCoefficient\InbreedingCoefficientUpdaterService;
use AppBundle\Util\ResultUtil;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;

class InbreedingCoefficientCalculationTaskService
{
    const TITLE = 'inbreeding_coefficient_calculation';

    /** @var EntityManager  */
    private $em;

    /** @var Logger  */
    private $logger;

    /** @var InbreedingCoefficientUpdaterService */
    private $inbreedingCoefficientUpdaterService;

    /**
     * InbreedingCoefficientCalculationTaskService constructor.
     * @param EntityManager $em
     * @param Logger $logger
     * @param InbreedingCoefficientUpdaterService $inbreedingCoefficientUpdaterService
     */
    public function __construct(
        EntityManager $em,
        Logger $logger,
        InbreedingCoefficientUpdaterService $inbreedingCoefficientUpdaterService
    )
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->inbreedingCoefficientUpdaterService = $inbreedingCoefficientUpdaterService;
    }

    /**
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    function calculate()
    {
        try {
            $this->inbreedingCoefficientUpdaterService->generateForAllAnimalsAndLitters();
            return ResultUtil::successResult('ok');
        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    function recalculate()
    {
        try {
            $this->inbreedingCoefficientUpdaterService->regenerateForAllAnimalsAndLitters();
            return ResultUtil::successResult('ok');
        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }
}
