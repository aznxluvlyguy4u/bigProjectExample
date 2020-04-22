<?php


namespace AppBundle\Service\Task;

use AppBundle\Service\InbreedingCoefficient\InbreedingCoefficientAllAnimalsUpdaterService;
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

    /** @var InbreedingCoefficientAllAnimalsUpdaterService */
    private $inbreedingCoefficientAllAnimalsUpdaterService;

    /**
     * InbreedingCoefficientCalculationTaskService constructor.
     * @param EntityManager $em
     * @param Logger $logger
     * @param InbreedingCoefficientAllAnimalsUpdaterService $inbreedingCoefficientAllAnimalsUpdaterService
     */
    public function __construct(
        EntityManager $em,
        Logger $logger,
        InbreedingCoefficientAllAnimalsUpdaterService $inbreedingCoefficientAllAnimalsUpdaterService
    )
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->inbreedingCoefficientAllAnimalsUpdaterService = $inbreedingCoefficientAllAnimalsUpdaterService;
    }

    /**
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    function calculate()
    {
        try {
            $this->inbreedingCoefficientAllAnimalsUpdaterService->generateForAllAnimalsAndLitters();
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
            $this->inbreedingCoefficientAllAnimalsUpdaterService->regenerateForAllAnimalsAndLitters();
            return ResultUtil::successResult('ok');
        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }
}
