<?php


namespace AppBundle\Service\InbreedingCoefficient;


use AppBundle\Entity\CalcInbreedingCoefficientParent;
use AppBundle\Entity\CalcInbreedingCoefficientParentDetails;
use AppBundle\Entity\InbreedingCoefficient;
use AppBundle\Entity\InbreedingCoefficientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class InbreedingCoefficientUpdaterService implements InbreedingCoefficientUpdaterServiceInterface
{
    private const BATCH_SIZE = 25;

    /** @var EntityManagerInterface */
    private $em;
    /** @var LoggerInterface */
    private $logger;
    /** @var InbreedingCoefficientRepository */
    private $inbreedingCoefficientRepository;

    /** @var int */
    private $updateCount = 0;
    /** @var int */
    private $newCount = 0;
    /** @var int */
    private $batchCount = 0;

    /** @var int */
    private $matchAnimalCount = 0;
    /** @var int */
    private $matchLitterCount = 0;


    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;

        $this->inbreedingCoefficientRepository = $this->em->getRepository(InbreedingCoefficient::class);
    }


    private function resetCounts()
    {
        $this->newCount = 0;
        $this->updateCount = 0;
        $this->batchCount = 0;
        $this->matchAnimalCount = 0;
        $this->matchLitterCount = 0;
    }

    public function generateInbreedingCoefficients(array $parentIdsPairs, bool $findGlobalMatch = false)
    {
        // TODO: Implement generateInbreedingCoefficients() method.
    }

    public function regenerateInbreedingCoefficients(array $parentIdsPairs, bool $findGlobalMatch = false)
    {
        // TODO: Implement regenerateInbreedingCoefficients() method.
    }

    public function matchAnimalsAndLitters($animalIds = [], $litterIds = [])
    {
        // TODO: Implement matchAnimalsAndLitters() method.
    }

    public function matchAnimalsAndLittersGlobal()
    {
        // TODO: Implement matchAnimalsAndLittersGlobal() method.
    }

    public function generateForAllAnimalsAndLitters()
    {
        // TODO: Implement generateForAllAnimalsAndLitters() method.
    }

    public function regenerateForAllAnimalsAndLitters()
    {
        // TODO: Implement regenerateForAllAnimalsAndLitters() method.
    }

    public function generateForAnimalsAndLittersOfUbn(string $ubn)
    {
        // TODO: Implement generateForAnimalsAndLittersOfUbn() method.
    }

    public function regenerateForAnimalsAndLittersOfUbn(string $ubn)
    {
        // TODO: Implement regenerateForAnimalsAndLittersOfUbn() method.
    }

    public function xyz()
    {
        $this->clearCalcTables();

        $year = 2013;
        $month = 3;
        $this->em->getRepository(CalcInbreedingCoefficientParent::class)->fillByYearAndMonth($year, $month, $this->logger);
        $this->em->getRepository(CalcInbreedingCoefficientParentDetails::class)->fillAll($this->logger);
    }


    public function clearCalcTables()
    {
        $this->em->getRepository(CalcInbreedingCoefficientParent::class)->truncate($this->logger);
        $this->em->getRepository(CalcInbreedingCoefficientParentDetails::class)->truncate($this->logger);
    }

}
