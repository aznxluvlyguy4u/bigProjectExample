<?php


namespace AppBundle\Service\Migration;


use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Entity\BreedIndexCoefficient;

class WormResistanceIndexMigrator extends IndexMigratorBase implements IndexMigratorInterface
{
    public function migrate()
    {
        $startDate = new \DateTime('2014-01-01');

        $wormResistanceIndexType = $this->getWormResistanceIndexType();
        $breedIndexCoefficients = $this->getBreedIndexCoefficients($wormResistanceIndexType);

        $igaScotlandBreedValueType = $this->getBreedValueType(BreedValueTypeConstant::IGA_SCOTLAND);

        if ($igaScotlandBreedValueType === null) {
            $this->getLogger()->warning('BreedType: '.BreedValueTypeConstant::IGA_SCOTLAND.' does not exist yet. Re-initialize BreedIndexTypes');
            return;
        }

        $createNewIgaScotlandBreedIndexCoefficient = true;

        $igaScotlandBreedIndexCoefficient = (new BreedIndexCoefficient())
            ->setBreedIndexType($wormResistanceIndexType)
            ->setBreedValueType($igaScotlandBreedValueType)
            ->setStartDate($startDate)
            ->setC(1)
            ->setVar(1)
            ->setT(1)
        ;

        $updateCount = 0;
        $insertCount = 0;

        /** @var BreedIndexCoefficient $breedIndexCoefficient */
        foreach ($breedIndexCoefficients as $breedIndexCoefficient)
        {
            switch ($breedIndexCoefficient->getBreedValueType()->getNl())
            {
                case BreedValueTypeConstant::IGA_SCOTLAND:
                    $createNewIgaScotlandBreedIndexCoefficient = false;
                    $updatedValues = $this->updateValues($breedIndexCoefficient, $igaScotlandBreedIndexCoefficient);
                    if($updatedValues) { $updateCount++; }
                    break;
            }
        }

        if($createNewIgaScotlandBreedIndexCoefficient) {
            $this->getManager()->persist($igaScotlandBreedIndexCoefficient);
            $insertCount++;
        }

        if($insertCount > 0 || $updateCount > 0) {
            $this->getManager()->flush();
        }

        $this->getLogger()->notice('WormResistanceMeatIndex insert|update: '.$insertCount.'|'.$updateCount);
    }

}