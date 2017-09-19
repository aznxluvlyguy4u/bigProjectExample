<?php


namespace AppBundle\Service\DataFix;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\Weight;
use AppBundle\Util\NumberUtil;
use AppBundle\Util\TimeUtil;

class MissingBirthMeasurementsService extends DataFixServiceBase
{
    const FILENAME = 'fill_missing_birth_weight_and_tail_length.csv';
    const INPUT_FOLDER = 'app/Resources/imports/corrections/';
    const OUTPUT_FOLDER = 'app/Resources/output/corrections/';


    public function run()
    {
        $this->parse();
        $this->getLogger()->notice('Processing '.count($this->getData()).' missing BirthWeights and TailLengths');
        foreach ($this->getData() as $record) {
            $this->processRecord($record);
        }
        $this->getLogger()->notice('DONE!');
    }


    /**
     * @param array $record
     * @throws \Exception
     */
    private function processRecord($record)
    {
        $ulnString = $record[0];
        $birthWeightValue = $record[1];
        $tailLengthValue = $record[2];

        $ulnParts = Utils::getUlnFromString($ulnString);
        $ulnCountryCode = $ulnParts[Constant::ULN_COUNTRY_CODE_NAMESPACE];
        $ulnNumber = $ulnParts[Constant::ULN_NUMBER_NAMESPACE];

        $declareBirths = $this->getManager()->getRepository(DeclareBirth::class)
            ->findBy(['ulnCountryCode'=>$ulnCountryCode, 'ulnNumber' => $ulnNumber]);

        $count = count($declareBirths);
        $this->validateUniqueResult($declareBirths, DeclareBirth::getShortClassName());

        /** @var DeclareBirth $declareBirth */
        $declareBirth =  $declareBirths[0];

        $animal = $declareBirth->getAnimal();
        if($animal === null) {
            throw new \Exception('ANIMAL MISSING');
        }


        if (!NumberUtil::areFloatsEqual($birthWeightValue, $declareBirth->getBirthWeight())) {
            $declareBirth->setBirthWeight($birthWeightValue);
            $this->getManager()->persist($declareBirth);
        }

        if (!NumberUtil::areFloatsEqual($tailLengthValue, $declareBirth->getBirthTailLength())) {
            $declareBirth->setBirthTailLength($tailLengthValue);
            $this->getManager()->persist($declareBirth);
        }



        // Create BirthWeight

        $birthWeights = $this->getManager()->getRepository(Weight::class)
            ->findBy(['isBirthWeight' => true, 'animal' => $animal, 'isActive' => true]);

        if(count($birthWeights) === 0) {
            $birthWeights = $this->getManager()->getRepository(Weight::class)
                ->findBy(['measurementDate' => $animal->getDateOfBirth(), 'animal' => $animal, 'isActive' => true]);
        } else {
            $createBirthWeight = false;
        }

        $createBirthWeight = true;
        if(count($birthWeights) === 0) {
            $birthWeights = $this->getManager()->getRepository(Weight::class)
                ->findBy(['weight' => $birthWeightValue, 'animal' => $animal, 'isActive' => true]);

            if (count($birthWeights) >= 2) { throw new \Exception('MORE THAN 1 BIRTH WEIGHT FOUND'); }

            if (count($birthWeights) === 1) {
                /** @var Weight $birthWeight */
                $birthWeight = $birthWeights[0];

                if(TimeUtil::getDaysBetween($birthWeight->getMeasurementDate(), $declareBirth->getDateOfBirth()) <= 3 ) {
                    $birthWeight->setIsBirthWeight(true);
                    $animal->addWeightMeasurement($birthWeight);
                    $this->getManager()->persist($birthWeight);
                    $this->getManager()->persist($animal);

                    $createBirthWeight = false;
                }
            }
        } elseif (count($birthWeights) >= 2) {
            throw new \Exception('MORE THAN 1 BIRTH WEIGHT FOUND');

        } elseif (count($birthWeights) === 1) {

            /** @var Weight $birthWeight */
            $birthWeight = $birthWeights[0];

            if (!$birthWeight->getIsBirthWeight()) {
                $birthWeight->setIsBirthWeight(true);
                $this->getManager()->persist($birthWeight);
            }

            $createBirthWeight = false;
        }


        // Create Birth Weight
        if($createBirthWeight) {
            $birthWeight = new Weight();
            $birthWeight->setMeasurementDate($declareBirth->getDateOfBirth());
            $birthWeight->setAnimal($animal);
            $birthWeight->setIsBirthWeight(true);
            $birthWeight->setWeight($birthWeightValue);
            $birthWeight->setAnimalIdAndDateByAnimalAndDateTime($animal, $declareBirth->getDateOfBirth());
            $animal->addWeightMeasurement($birthWeight);
            $this->getManager()->persist($birthWeight);
        }



        $tailLength = $this->getManager()->getRepository(TailLength::class)->findOneBy(['animal' => $animal]);

        // Create Tail Length
        if($tailLength === null || !NumberUtil::areFloatsEqual($tailLength->getLength(), $tailLengthValue)) {
            $tailLength = new TailLength();
            $tailLength->setMeasurementDate($declareBirth->getDateOfBirth());
            $tailLength->setAnimal($animal);
            $tailLength->setLength($tailLengthValue);
            $tailLength->setAnimalIdAndDateByAnimalAndDateTime($animal, $declareBirth->getDateOfBirth());

            $animal->addTailLengthMeasurement($tailLength);
            $this->getManager()->persist($tailLength);
            $this->getManager()->persist($animal);
        }


        $this->getManager()->flush();

        $this->getLogger()->notice('Processed: '.$ulnString);
    }


    /**
     * @param array $results
     * @param string $className
     * @throws \Exception
     */
    private function validateUniqueResult($results, $className)
    {
        $count = count($results);
        $className = strtoupper($className);
        if ($count === 0) {
            throw new \Exception('NO '.$className.' FOUND!');
        } elseif ($count >= 2) {
            throw new \Exception('MORE THAN 1 '.$className.' FOUND');
        }
    }


}