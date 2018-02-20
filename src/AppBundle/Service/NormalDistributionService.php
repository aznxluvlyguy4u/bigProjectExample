<?php


namespace AppBundle\Service;


use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Entity\BreedValue;
use AppBundle\Entity\LambMeatBreedIndex;
use AppBundle\Entity\NormalDistribution;
use AppBundle\Entity\NormalDistributionRepository;
use AppBundle\Enumerator\BreedValueCoefficientType;
use AppBundle\Util\MathUtil;
use AppBundle\Util\NumberUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;

class NormalDistributionService
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Logger */
    private $logger;

    /** @var NormalDistributionRepository */
    private $normalDistributionRepository;


    public function __construct(EntityManagerInterface $em, Logger $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }


    public function onInit()
    {
        /** @var NormalDistributionRepository $normalDistributionRepository */
        $this->normalDistributionRepository = $this->getManager()->getRepository(NormalDistribution::class);
    }

    /**
     * @return NormalDistributionRepository
     */
    public function getNormalDistributionRepository()
    {
        return $this->normalDistributionRepository;
    }


    /**
     * @return EntityManagerInterface
     */
    public function getManager()
    {
        return $this->em;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }


    /**
     * @param $generationDate
     * @throws \Exception
     */
    public function persistLambMeatIndexMeanAndStandardDeviation($generationDate)
    {
        $type = BreedValueCoefficientType::LAMB_MEAT_INDEX;
        // NOTE! It is assumed that generationDate->year = measurementDate->year
        $year = TimeUtil::getYearFromDateTimeString($generationDate);

        foreach ([true, false] as $isIncludingOnlyAliveAnimals) {
            $lambMeatIndexValues = $this->getManager()->getRepository(LambMeatBreedIndex::class)
                ->getValues($generationDate, $isIncludingOnlyAliveAnimals);

            self::upsertMeanAndStandardDeviation($type, $year, $isIncludingOnlyAliveAnimals, $lambMeatIndexValues);
        }
    }


    /**
     * @param string|\DateTime $generationDate
     * @throws \Exception
     */
    public function persistWormResistanceMeanAndStandardDeviationSIgA($generationDate)
    {
        $breedValueTypeConstant = BreedValueTypeConstant::IGA_SCOTLAND;

        $yearsInMeasurementSet = $this->getManager()->getRepository(BreedValue::class)
            ->getMeasurementYearsOfGenerationSet($generationDate, $breedValueTypeConstant);

        foreach ($yearsInMeasurementSet as $year) {
            foreach ([true, false] as $isIncludingOnlyAliveAnimals) {
                $valuesArray = $this->getManager()->getRepository(BreedValue::class)
                    ->getReliableSIgAValues($year, $isIncludingOnlyAliveAnimals);

                self::upsertMeanAndStandardDeviation($breedValueTypeConstant, $year, $isIncludingOnlyAliveAnimals, $valuesArray);
            }
        }
    }


    /**
     * @param int|string $year
     * @param string $type
     * @param boolean $isIncludingOnlyAliveAnimals
     * @param array $valuesArray
     */
    private function upsertMeanAndStandardDeviation($type, $year,
                                                    $isIncludingOnlyAliveAnimals, array $valuesArray = [])
    {
        if (count($valuesArray) === 0) {
            return;
        }

        $mean = array_sum($valuesArray) / count($valuesArray);
        $standardDeviation = MathUtil::standardDeviation($valuesArray, $mean);

        $normalDistribution = $this->getNormalDistributionRepository()
            ->findOneBy(['year' => $year, 'type' => $type, 'isIncludingOnlyAliveAnimals' => $isIncludingOnlyAliveAnimals]);

        if($normalDistribution instanceof NormalDistribution) {
            /** @var NormalDistribution $normalDistribution */

            //Update values if necessary
            if(!NumberUtil::areFloatsEqual($normalDistribution->getMean(), $mean) || !NumberUtil::areFloatsEqual($normalDistribution->getStandardDeviation(), $standardDeviation)) {
                $normalDistribution->setMean($mean);
                $normalDistribution->setStandardDeviation($standardDeviation);
                $normalDistribution->setLogDate(new \DateTime());

                $this->getManager()->persist($normalDistribution);
                $this->getManager()->flush();
            }
        } else {
            //Create a new entry
            $this->getNormalDistributionRepository()
                ->persistFromValues($type, $year, $mean, $standardDeviation, $isIncludingOnlyAliveAnimals);
        }
    }
}