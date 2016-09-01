<?php

namespace AppBundle\Command;

use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\BodyFat;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Fat1;
use AppBundle\Entity\Fat2;
use AppBundle\Entity\Fat3;
use AppBundle\Entity\Inspector;
use AppBundle\Entity\Location;
use AppBundle\Entity\MuscleThickness;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\Weight;
use AppBundle\Enumerator\GenderType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoMigrateMeasurements2016Command extends ContainerAwareCommand
{
    const TITLE = 'Migrate MeasurementsData for 2016: meetwaardenoverzicht2016(fixed-formatting).csv';
    const INPUT_PATH = '/home/data/JVT/projects/NSFO/Migratie/Animal/animal_measurements_2016.csv';
    const BATCH_COUNT = 100;

    /** @var ObjectManager $em */
    private $em;

    /** @var int */
    private $animalsFoundCount;

    /** @var int */
    private $animalsNotFoundCount;

    /** @var ArrayCollection */
    private $missingUbns;

    /** @var ArrayCollection */
    private $missingAnimals;

    /** @var AnimalRepository */
    private $animalRepository;
    
    /** @var int */
    private $foundByPedigreeCode;
    
    /** @var int */
    private $foundByUbnAndAnimalOrderNumber;

    /** @var InputInterface */
    private $inputInterface;

    /** @var OutputInterface */
    private $outputInterface;

    protected function configure()
    {
        $this
            ->setName('nsfo:migrate:measurements2016')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);

        $this->animalRepository = $em->getRepository(Animal::class);

        $this->inputInterface = $input;
        $this->outputInterface = $output;

        $this->missingUbns = new ArrayCollection();
        $this->missingAnimals = new ArrayCollection();
        $this->animalsFoundCount = 0;
        $this->animalsNotFoundCount = 0;

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));
        $output->writeln(['it is assumed there are no duplicate measurements in the csv']);

        $data = $cmdUtil->getRowsFromCsvFileWithoutHeader(self::INPUT_PATH);

        $cmdUtil->setStartTimeAndPrintIt(count($data));

        $rowCount = 0;
        foreach ($data as $row) {

            $this->processRow($row);
            //Flush after each row to prevent duplicates
            DoctrineUtil::flushClearAndGarbageCollect($em);
            $rowCount++;

            $cmdUtil->advanceProgressBar(1, 'Rows processed: '.$rowCount);

        }
        DoctrineUtil::flushClearAndGarbageCollect($em);
        $output->writeln(['','Rows processed: '.$rowCount]);
        

        $output->writeln(['===============','Missing ubns: ']);
        foreach ($this->missingUbns as $ubn) {
            $output->writeln($ubn);
        }
        $output->writeln('===============');

        $output->writeln('Missing pedigreeCodes: ');
        foreach ($this->missingAnimals as $pedigreeCode) {
            $output->writeln($pedigreeCode);
        }
        $output->writeln('===============');

        $output->writeln([
            '=== Results ===',
            'AnimalNotFound: '.$this->animalsNotFoundCount,
            'AnimalFound: '.$this->animalsFoundCount,
            'Rows processed (incl header and empty rows): '.$rowCount,
            '']);

        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }
    

    /**
     * @param string $row
     */
    private function processRow($row)
    {
        $rowParts = explode(';',$row);
        
        //null Check
        if(sizeof($rowParts) < 11) {
            $this->outputInterface->writeln('invalid row: '.$row);
            return;
        }

        $ubn = $rowParts[0];
        $animalOrderNumber = strval(sprintf('%05d', $rowParts[2]));
        $pedigreeCode = $rowParts[3];

        $foundAnimal = $this->findAnimal($pedigreeCode, $ubn, $animalOrderNumber);
        
        if($foundAnimal != null) {

            $measurementDateValue = $rowParts[1];
            $weightValue = $rowParts[4];
            $muscleThicknessValue = $rowParts[5];
            $fat1Value = $rowParts[6];
            $fat2Value = $rowParts[7];
            $fat3Value = $rowParts[8];
            $tailLengthValue = $rowParts[9];            
            $inspectorFullName = $rowParts[10];

            $inspectorExists = NullChecker::isNotNull($inspectorFullName);
            $measurementDateValueExists = NullChecker::isNotNull($measurementDateValue);
            $weightValueExists = NullChecker::numberIsNotNull($weightValue);
            $muscleThicknessValueExists = NullChecker::numberIsNotNull($muscleThicknessValue);
            $fat1ValueExists = NullChecker::numberIsNotNull($fat1Value);
            $fat2ValueExists = NullChecker::numberIsNotNull($fat2Value);
            $fat3ValueExists = NullChecker::numberIsNotNull($fat3Value);
            $tailLengthValueExists = NullChecker::numberIsNotNull($tailLengthValue);

            if($measurementDateValueExists) {
                $measurementDate = new \DateTime($measurementDateValue);
            } else {
                $measurementDate = null;
            }

            $inspector = null;
            if($inspectorExists) {
                $inspector = $this->em->getRepository(Inspector::class)->findOneByLastName($inspectorFullName);
                if($inspector == null) {
                    $inspector = new Inspector();
                    $inspector->setLastName($inspectorFullName);
                    //set empty/default values for not-nullable fields
                    $inspector->setFirstName(' ');
                    $inspector->setEmailAddress(' ');
                    $inspector->setPassword('NEW_CLIENT');
                    $this->em->persist($inspector);
                    $this->em->flush();
                }
                //Don't persist duplicates!
            }

            if($weightValueExists) {

                $weight = new Weight();
                $weight->setMeasurementDate($measurementDate);
                if($inspectorExists) { $weight->setInspector($inspector); }

                if($this->isBirthWeight($measurementDate, $foundAnimal)) {
                    $weight->setIsBirthWeight(true);
                } else {
                    $weight->setIsBirthWeight(false);
                }
                $weight->setWeight($weightValue);
                $weight->setAnimal($foundAnimal);

                /** @var Weight $foundWeight */
                $foundWeight = $this->em->getRepository(Weight::class)
                    ->findOneBy(['measurementDate' => $measurementDate, 'animal' => $foundAnimal]);

                if($foundWeight == null) {
                    $foundAnimal->addWeightMeasurement($weight);
                    $this->em->persist($weight);

                } else if(!$weight->isEqualInValues($foundWeight)) {
                    $foundAnimal->addWeightMeasurement($weight);
                    $this->em->persist($weight);

                    $foundWeight->setIsRevoked(true);
                    $this->em->persist($foundWeight);
                }
                //Don't persist duplicates!
            }


            if($muscleThicknessValueExists) {
                $muscleThickness = new MuscleThickness();
                $muscleThickness->setMeasurementDate($measurementDate);
                if($inspectorExists) { $muscleThickness->setInspector($inspector); }

                $muscleThickness->setMuscleThickness($muscleThicknessValue);
                $muscleThickness->setAnimal($foundAnimal);

                /** @var MuscleThickness $foundMuscleThickness */
                $foundMuscleThickness = $this->em->getRepository(MuscleThickness::class)
                    ->findOneBy(['measurementDate' => $measurementDate, 'animal' => $foundAnimal]);

                if($foundMuscleThickness == null) {
                    $foundAnimal->addMuscleThicknessMeasurement($muscleThickness);
                    $this->em->persist($muscleThickness);

                } else if(!$muscleThickness->isEqualInValues($foundMuscleThickness)) {
                    //overwrite the old values in the found MuscleThickness
                    $foundMuscleThickness->setMuscleThickness($muscleThicknessValue);
                    $foundMuscleThickness->setInspector($inspector);
                    $this->em->persist($foundMuscleThickness);
                }
                //Don't persist duplicates!
            }


            if($fat1ValueExists || $fat2ValueExists || $fat3ValueExists) {

                /** @var BodyFat $foundBodyFat */
                $foundBodyFat = $this->em->getRepository(BodyFat::class)
                    ->findOneBy(['measurementDate' => $measurementDate, 'animal' => $foundAnimal]);

                if($foundBodyFat == null) {

                    $bodyFat = new BodyFat();
                    if($inspectorExists) { $bodyFat->setInspector($inspector); }
                    $bodyFat->setMeasurementDate($measurementDate);

                    if($fat1ValueExists) {
                        $fat1 = new Fat1();
                        if($inspectorExists) { $fat1->setInspector($inspector); }
                        $fat1->setMeasurementDate($measurementDate);

                        $fat1->setFat($fat1Value);

                        $fat1->setBodyFat($bodyFat);
                        $bodyFat->setFat1($fat1);
                        $this->em->persist($fat1);
                    }

                    if($fat2ValueExists) {
                        $fat2 = new Fat2();
                        if($inspectorExists) { $fat2->setInspector($inspector); }
                        $fat2->setMeasurementDate($measurementDate);

                        $fat2->setFat($fat2Value);

                        $fat2->setBodyFat($bodyFat);
                        $bodyFat->setFat2($fat2);
                        $this->em->persist($fat2);
                    }

                    if($fat3ValueExists) {
                        $fat3 = new Fat3();
                        if($inspectorExists) { $fat3->setInspector($inspector); }
                        $fat3->setMeasurementDate($measurementDate);

                        $fat3->setFat($fat3Value);

                        $fat3->setBodyFat($bodyFat);
                        $bodyFat->setFat3($fat3);
                        $this->em->persist($fat3);
                    }

                    $bodyFat->setAnimal($foundAnimal);
                    $foundAnimal->addBodyFatMeasurement($bodyFat);
                    $this->em->persist($bodyFat);

                } else if(!$foundBodyFat->hasValues($measurementDate, $foundAnimal, $inspector, $fat1Value, $fat2Value, $fat3Value)) {
                    //overwrite old values in foundBodyFat
                    if($fat1ValueExists) {
                        $fat1 = $foundBodyFat->getFat1();
                        if($fat1 == null) {
                            $fat1 = new Fat1();
                            $foundBodyFat->setFat1($fat1);
                            if($inspectorExists) { $fat1->setInspector($inspector); }
                            $fat1->setMeasurementDate($measurementDate);
                            $fat1->setBodyFat($foundBodyFat);
                            $this->em->persist($fat1);
                        }
                        $foundBodyFat->getFat1()->setFat($fat1Value);
                    }


                    if($fat2ValueExists) {
                        $fat2 = $foundBodyFat->getFat2();
                        if($fat2 == null) {
                            $fat2 = new Fat2();
                            $foundBodyFat->setFat2($fat2);
                            if($inspectorExists) { $fat2->setInspector($inspector); }
                            $fat2->setMeasurementDate($measurementDate);
                            $fat2->setBodyFat($foundBodyFat);
                            $this->em->persist($fat2);
                        }
                        $foundBodyFat->getFat2()->setFat($fat2Value);
                    }


                    if($fat3ValueExists) {
                        $fat3 = $foundBodyFat->getFat3();
                        if($fat3 == null) {
                            $fat3 = new Fat3();
                            $foundBodyFat->setFat3($fat3);
                            if($inspectorExists) { $fat3->setInspector($inspector); }
                            $fat3->setMeasurementDate($measurementDate);
                            $fat3->setBodyFat($foundBodyFat);
                            $this->em->persist($fat3);
                        }
                        $foundBodyFat->getFat3()->setFat($fat3Value);
                    }

                    $foundBodyFat->setInspector($inspector);
                    $this->em->persist($foundBodyFat);
                }
                //Don't persist duplicates!
            }


            if($tailLengthValueExists) {

                /** @var TailLength $foundTailLength */
                $foundTailLength = $this->em->getRepository(TailLength::class)
                    ->findOneBy(['measurementDate' => $measurementDate, 'animal' => $foundAnimal]);

                $tailLength = new TailLength();
                $tailLength->setMeasurementDate($measurementDate);
                if($inspectorExists) { $tailLength->setInspector($inspector); }

                $tailLength->setLength($tailLengthValue);
                $tailLength->setAnimal($foundAnimal);

                if($foundTailLength == null) {

                    $foundAnimal->addTailLengthMeasurement($tailLength);
                    $this->em->persist($tailLength);

                } else if (!$foundTailLength->isEqualInValues($tailLength)) {

                    $foundTailLength->setLength($tailLengthValue);
                    $this->em->persist($foundTailLength);

                }
                //Don't persist duplicates!

            }

            $this->em->persist($foundAnimal);
        }
    }


    /**
     * @param string $pedigreeCode
     * @param string $ubn
     * @param string $animalOrderNumber
     * @return Animal|Ewe|Neuter|Ram|null
     */
    private function findAnimal($pedigreeCode, $ubn, $animalOrderNumber)
    {
        $foundAnimal = $this->findAnimalByPedigreeCode($pedigreeCode);

        if($foundAnimal != null) {
            $this->foundByPedigreeCode++;
            $this->animalsFoundCount++;
        } else {
            $foundAnimal = $this->findAnimalByUbnAndAnimalOrder($ubn, $animalOrderNumber);
            if($foundAnimal != null) {
                $this->foundByUbnAndAnimalOrderNumber++;
                $this->animalsFoundCount++;
            } else {
                $this->missingAnimals->set($pedigreeCode, $ubn. ';' . $pedigreeCode. ';' .$animalOrderNumber);
                $this->animalsNotFoundCount++;
            }
        }
        return $foundAnimal;
    }


    /**
     * @param string $pedigreeCode
     * @return Animal|Ewe|Neuter|Ram|null
     */
    private function findAnimalByPedigreeCode($pedigreeCode)
    {
        $pedigreeCodeParts = StringUtil::getStnFromCsvFileString($pedigreeCode);

        return $this->animalRepository->findByPedigreeCountryCodeAndNumber(
            $pedigreeCodeParts[JsonInputConstant::PEDIGREE_COUNTRY_CODE],
            $pedigreeCodeParts[JsonInputConstant::PEDIGREE_NUMBER]
        );
    }


    /**
     * @param string $ubn
     * @param string $animalOrderNumber
     * @return Animal|Ewe|Neuter|Ram|null
     */
    private function findAnimalByUbnAndAnimalOrder($ubn, $animalOrderNumber)
    {
        $location = $this->em->getRepository(Location::class)->findByUbn($ubn);

        if($location != null) {
            return $this->em->getRepository(Animal::class)->findOneBy(['animalOrderNumber' => $animalOrderNumber, 'location' => $location]);
        } else {
            $this->missingUbns->set($ubn, $ubn);
        }
        return null;
    }

    /**
     * Note it is assumed both inputs are already null checked
     *
     * @param \DateTime $measurementDateTime
     * @param Animal $animal
     * @return boolean
     */
    private function isBirthWeight($measurementDateTime, $animal)
    {
        $dateTimeOfBirth = $animal->getDateOfBirth();

        if($dateTimeOfBirth != null) {

            if(TimeUtil::isDateTimesOnTheSameDay($dateTimeOfBirth, $measurementDateTime)) {
                return true;
            }
        }
        return false;
    }


    private function dumpRowValues($row)
    {
        $rowParts = explode(';',$row);

        //null Check
        if(sizeof($rowParts) < 11) {
            dump('invalid row: '.$row);die;
        }

        $ubn = $rowParts[0];
        $animalOrderNumber = strval(sprintf('%05d', $rowParts[2]));
        $pedigreeCode = $rowParts[3];

        $measurementDateValue = $rowParts[1];
        $weightValue = $rowParts[4];
        $muscleThicknessValue = $rowParts[5];
        $fat1Value = $rowParts[6];
        $fat2Value = $rowParts[7];
        $fat3Value = $rowParts[8];
        $tailLengthValue = $rowParts[9];
        $inspectorFullName = $rowParts[10];

        dump(['ubn' => $ubn, 'date' => $measurementDateValue, 'animalOrderNumber' => $animalOrderNumber, 'pedigree' => $pedigreeCode,
            'weight' => $weightValue, 'muscle' => $muscleThicknessValue, 'fat1' => $fat1Value, 'fat2' => $fat2Value, 'fat3' => $fat3Value,
            'tail' => $tailLengthValue, 'inspector' => $inspectorFullName]);die;
    }
}
