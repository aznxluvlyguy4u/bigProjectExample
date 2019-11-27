<?php


namespace AppBundle\Service\Task;

use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Util\ResultUtil;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;

class StarEwesCalculationTaskService
{
    const TITLE = 'star_ewes_calculation';

    /** @var EntityManager  */
    private $em;

    /** @var Logger  */
    private $logger;

    /**
     * StarEwesCalculationTaskService constructor.
     * @param EntityManager $em
     * @param Logger $logger
     */
    public function __construct(
        EntityManager $em,
        Logger $logger
    )
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @param Person $person
     * @param Location|null $location
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    function calculate(Person $person, ?Location $location = null)
    {
        // LOGIC HERE!!
        try {

            return ResultUtil::successResult('ok');
        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), $exception->getCode());
        }
    }
}
