<?php


namespace AppBundle\Migration;

use AppBundle\Constant\BreedIndexTypeConstant;
use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Entity\BreedIndexCoefficient;
use AppBundle\Entity\BreedIndexCoefficientRepository;
use AppBundle\Entity\BreedIndexType;
use AppBundle\Entity\BreedValueType;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class LambMeatIndexMigrator
 * @package AppBundle\Migration
 */
class LambMeatIndexMigrator
{
    /** @var ObjectManager */
    private $em;
    /** @var Connection */
    private $conn;
    /** @var Logger */
    private $logger;
    /** @var BreedIndexCoefficientRepository */
    private $breedIndexCoefficientRepository;

    /**
     * LambMeatIndexMigrator constructor.
     * @param ObjectManager $em
     * @param Logger $logger
     */
    public function __construct(ObjectManager $em, Logger $logger)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->logger = $logger;
        /** @var BreedIndexCoefficientRepository breedIndexCoefficientRepository */
        $this->breedIndexCoefficientRepository = $em->getRepository(BreedIndexCoefficient::class);
    }


    public function migrate()
    {
        $startDate = new \DateTime('2016-01-01');

        $lambMeatIndexType = $this->getLambMeatIndexType();
        $breedIndexCoefficients = $this->breedIndexCoefficientRepository->findBy(['breedIndexType' => $lambMeatIndexType]);

        $growthBreedValueType = $this->getBreedValueType(BreedValueTypeConstant::GROWTH);
        $fatThicknessBreedValueType = $this->getBreedValueType(BreedValueTypeConstant::FAT_THICKNESS_3);
        $muscleThicknessBreedValueType = $this->getBreedValueType(BreedValueTypeConstant::MUSCLE_THICKNESS);

        $createNewGrowthBreedIndexCoefficient = true;
        $createNewFatBreedIndexCoefficient = true;
        $createNewMuscleThicknessBreedIndexCoefficient = true;

        $growthBreedIndexCoefficient = (new BreedIndexCoefficient())
            ->setBreedIndexType($lambMeatIndexType)
            ->setBreedValueType($growthBreedValueType)
            ->setStartDate($startDate)
            ->setC(200)
            ->setVar(0.0004839)
            ->setT(23.42076)
            ;

        $fatThicknessBreedIndexCoefficient = (new BreedIndexCoefficient())
            ->setBreedIndexType($lambMeatIndexType)
            ->setBreedValueType($fatThicknessBreedValueType)
            ->setStartDate($startDate)
            ->setC(-6.34)
            ->setVar(0.157)
            ->setT(6.3107092)
        ;

        $muscleThicknessBreedIndexCoefficient = (new BreedIndexCoefficient())
            ->setBreedIndexType($lambMeatIndexType)
            ->setBreedValueType($muscleThicknessBreedValueType)
            ->setStartDate($startDate)
            ->setC(3.7)
            ->setVar(1.848)
            ->setT(25.29912)
        ;

        $updateCount = 0;
        $insertCount = 0;

        /** @var BreedIndexCoefficient $breedIndexCoefficient */
        foreach ($breedIndexCoefficients as $breedIndexCoefficient)
        {
            switch ($breedIndexCoefficient->getBreedValueType()->getNl())
            {
                case BreedValueTypeConstant::GROWTH:
                    $createNewGrowthBreedIndexCoefficient = false;
                    $updatedValues = $this->updateValues($breedIndexCoefficient, $growthBreedIndexCoefficient);
                    if($updatedValues) { $updateCount++; }
                    break;

                case BreedValueTypeConstant::FAT_THICKNESS_3:
                    $createNewFatBreedIndexCoefficient = false;
                    $updatedValues = $this->updateValues($breedIndexCoefficient, $fatThicknessBreedIndexCoefficient);
                    if($updatedValues) { $updateCount++; }
                    break;

                case BreedValueTypeConstant::MUSCLE_THICKNESS:
                    $createNewMuscleThicknessBreedIndexCoefficient = false;
                    $updatedValues = $this->updateValues($breedIndexCoefficient, $fatThicknessBreedIndexCoefficient);
                    if($updatedValues) { $updateCount++; }
                    break;
            }
        }

        if($createNewGrowthBreedIndexCoefficient) {
            $this->em->persist($growthBreedIndexCoefficient);
            $insertCount++;
        }

        if($createNewFatBreedIndexCoefficient) {
            $this->em->persist($fatThicknessBreedIndexCoefficient);
            $insertCount++;
        }

        if($createNewMuscleThicknessBreedIndexCoefficient) {
            $this->em->persist($muscleThicknessBreedIndexCoefficient);
            $insertCount++;
        }

        if($insertCount > 0 || $updateCount > 0) {
            $this->em->flush();
        }

        $this->logger->notice('LambMeatIndex insert|update: '.$insertCount.'|'.$updateCount);
    }


    /**
     * @param BreedIndexCoefficient $retrievedBreedIndexCoefficient
     * @param BreedIndexCoefficient $referenceBreedIndexCoefficient
     * @return bool
     */
    public function updateValues(BreedIndexCoefficient $retrievedBreedIndexCoefficient, BreedIndexCoefficient$referenceBreedIndexCoefficient)
    {
        if(
            $retrievedBreedIndexCoefficient->getC() !== $referenceBreedIndexCoefficient->getC()
            || $retrievedBreedIndexCoefficient->getVar() !== $referenceBreedIndexCoefficient->getVar()
            || $retrievedBreedIndexCoefficient->getT() !== $referenceBreedIndexCoefficient->getT()
        ) {
            $retrievedBreedIndexCoefficient->setC($referenceBreedIndexCoefficient->getC());
            $retrievedBreedIndexCoefficient->setVar($referenceBreedIndexCoefficient->getVar());
            $retrievedBreedIndexCoefficient->setT($referenceBreedIndexCoefficient->getT());
            $this->em->persist($retrievedBreedIndexCoefficient);
            return true;
        }
        return false;
    }


    /**
     * @return BreedIndexType
     */
    private function getLambMeatIndexType()
    {
        return $this->em->getRepository(BreedIndexType::class)->findOneBy(['nl'=>BreedIndexTypeConstant::LAMB_MEAT_INDEX]);
    }


    /**
     * @param $breedValueTypeConstant
     * @return BreedValueType
     */
    private function getBreedValueType($breedValueTypeConstant)
    {
        return $this->em->getRepository(BreedValueType::class)->findOneBy(['nl'=>$breedValueTypeConstant]);
    }
}