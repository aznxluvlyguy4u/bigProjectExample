<?php


namespace AppBundle\Service;


use AppBundle\Entity\BreedValue;
use AppBundle\Entity\LambMeatBreedIndex;
use AppBundle\Entity\NormalDistribution;
use AppBundle\Entity\NormalDistributionRepository;
use AppBundle\Enumerator\BreedValueCoefficientType;
use AppBundle\Util\DateUtil;
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
     * Note the standard deviation will be based on the generation date of the breed values!
     *
     * @param $generationDate
     * @throws \Exception
     */
    public function persistLambMeatIndexMeanAndStandardDeviation($generationDate)
    {
        $type = BreedValueCoefficientType::LAMB_MEAT_INDEX;
        $year = TimeUtil::getYearFromDateTimeString($generationDate);

        foreach ([true, false] as $isIncludingOnlyAliveAnimals) {
            $lambMeatIndexValues = $this->getManager()->getRepository(LambMeatBreedIndex::class)
                ->getValues($generationDate, $isIncludingOnlyAliveAnimals);

            self::upsertMeanAndStandardDeviation($type, $year, $isIncludingOnlyAliveAnimals, $lambMeatIndexValues);
        }
    }


    /**
     * Note the standard deviation will be based on the generation date of the breed values!
     *
     * @param string $breedValueTypeConstant from BreedValueTypeConstant
     * @param string|\DateTime $generationDate
     * @param boolean $overwriteExisting
     * @throws \Exception
     */
    public function persistBreedValueTypeMeanAndStandardDeviation($breedValueTypeConstant, $generationDate, $overwriteExisting = false)
    {
        $generationYear = DateUtil::getYearFromDateStringOrDateTime($generationDate);

        $normalDistribution = $this->normalDistributionRepository
            ->getByBreedValueTypeAndYear($breedValueTypeConstant, $generationYear);
        if ($normalDistribution && !$overwriteExisting) {
            return;
        }

        foreach ([true, false] as $isIncludingOnlyAliveAnimals) {
            $valuesArray = $this->getManager()->getRepository(BreedValue::class)
                ->getReliableBreedValues($breedValueTypeConstant, $generationDate, $isIncludingOnlyAliveAnimals);

            self::upsertMeanAndStandardDeviation($breedValueTypeConstant,
                $generationYear, $isIncludingOnlyAliveAnimals, $valuesArray);
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


    /**
     * @param array $valuesArray
     * @return NormalDistribution|null
     */
    public static function getMeanAndStandardDeviation(array $valuesArray = [])
    {
        if (count($valuesArray) === 0) {
            return null;
        }

        $mean = array_sum($valuesArray) / count($valuesArray);
        $standardDeviation = MathUtil::standardDeviation($valuesArray, $mean);

        return (new NormalDistribution(
            null,
            null,
            $mean,
            $standardDeviation,
            false)
        );
    }
}