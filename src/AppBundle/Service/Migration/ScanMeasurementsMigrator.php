<?php


namespace AppBundle\Service\Migration;


use AppBundle\Cache\WeightCacher;
use AppBundle\Entity\Animal;
use AppBundle\Entity\BodyFat;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\Fat1;
use AppBundle\Entity\Fat2;
use AppBundle\Entity\Fat3;
use AppBundle\Entity\Measurement;
use AppBundle\Entity\MuscleThickness;
use AppBundle\Entity\Weight;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\MeasurementsUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;

class ScanMeasurementsMigrator extends Migrator2017JunServiceBase implements IMigratorService
{
    const IMPORT_SUB_FOLDER = 'measurements';
    const FILENAME = 'scan_measurements.csv';

    const DATE_TIME_FORMAT = 'Y-m-d';

    const BATCH_SIZE = 250;

    /** @var string[]*/
    private $missingAnimalUlns;

    /** @var int */
    private $newWeightsCount;
    /** @var int */
    private $newBodyFatsCount;
    /** @var int */
    private $newMuscleThicknessesCount;
    /** @var int */
    private $batchCount;
    /** @var int */
    private $processedCount;
    /** @var int */
    private $skippedWeightCount;
    /** @var int */
    private $skippedBodyFatCount;
    /** @var int */
    private $skippedMuscleThicknessCount;
    /** @var string */
    private $currentKey;
    /** @var string */
    private $lastProcessedKey;

    /** @inheritdoc */
    public function __construct(ObjectManager $em, $rootDir)
    {
        parent::__construct($em, $rootDir, self::BATCH_SIZE, self::IMPORT_SUB_FOLDER);

        $this->filenames = [
            self::FILENAME => self::FILENAME,
        ];
    }

    /** @inheritDoc */
    function run(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        throw new \Exception('The logic for this migration is outdated. Please also create a scan_measurement_set record and link it to the correct animal');

        $this->currentKey = $this->cmdUtil->questionForIntChoice(0, 'data import record');

        $this->writeLn('====== PRE migration fixes ======');
        $this->data = $this->parseCSV(self::FILENAME);

        $this->createInspectorSearchArrayAndInsertNewInspectors(9);
        MeasurementsUtil::generateAnimalIdAndDateValues($this->conn, false, $cmdUtil);

        $this->writeln('====== Validate data ======');
        if (!$this->validateData()) {
            return;
        }

        $this->writeln('====== Migrate scan measurements ======');
        $this->migrateNewScanMeasurements();

        $this->writeLn('====== POST migration updates ======');
        $this->cmdUtil->writeln( WeightCacher::updateAllWeights($this->conn) . ' weight cache records updated');
    }


    private function validateData()
    {
        $errors = [];

        foreach ($this->data as $record) {
            $ulnCountryCode = trim(strtoupper($record[0]));
            $ulnNumber = trim($record[1]);
            // $ubn = $record[2]; ignored
            $measurementDateString = trim($record[3]);
            $scanWeight = $record[4];
            $fat1 = $record[5];
            $fat2 = $record[6];
            $fat3 = $record[7];
            $muscleThickness = $record[8];
            $inspectorFullName = trim($record[9]);

            $ulnString = $ulnCountryCode . $ulnNumber;
            if (!Validator::verifyUlnFormat($ulnString, false)) {
                $errors[] = 'INVALID ULN FORMAT: ' . $ulnCountryCode . ' ' . $ulnNumber;
            }

            if (!TimeUtil::isValidDateTime($measurementDateString, self::DATE_TIME_FORMAT)) {
                $errors[] = 'INVALID DATETIME: ' . $measurementDateString;
            }

            $scanWeightValidationResult = $this->validateMeasurementValue($scanWeight, 100, false);
            if ($scanWeightValidationResult) {
                $errors[] = $scanWeightValidationResult;
            }

            $fat1ValidationResult = $this->validateMeasurementValue($fat1, 100, false);
            if ($fat1ValidationResult) {
                $errors[] = $fat1ValidationResult;
            }

            $fat2ValidationResult = $this->validateMeasurementValue($fat2, 100, false);
            if ($fat2ValidationResult) {
                $errors[] = $fat2ValidationResult;
            }

            $fat3ValidationResult = $this->validateMeasurementValue($fat3, 100, false);
            if ($fat3ValidationResult) {
                $errors[] = $fat3ValidationResult;
            }

            if (!($fat1 && $fat2 && $fat3)
                && !($fat1 === null && $fat2 === null && $fat3 === null)
                && !($fat1 === '' && $fat2 === '' && $fat3 === '')
            ) {
                $errors[] = 'NOT ALL BODY FAT VALUES ARE GIVEN: ' . $fat1 . ' - '. $fat2 . ' - '.$fat3;
            }

            $muscleThicknessValidationResult = $this->validateMeasurementValue($muscleThickness, 250, false);
            if ($muscleThicknessValidationResult) {
                $errors[] = $muscleThicknessValidationResult;
            }
        }

        if (count($errors) === 0) {
            return true;
        }

        $this->writeLn('====== INVALID DATA ======');
        foreach ($errors as $error) {
            $this->writeLn($error);
        }
        return false;
    }


    /**
     * @param float $value
     * @param float $maxValue
     * @param boolean $allowZero
     * @return null|string
     */
    private function validateMeasurementValue($value, $maxValue, $allowZero)
    {
        if($value !== null && (is_float($value) || Validator::isStringAFloat($value))) {
            $value = floatval($value);
            if ($value < 0) {
                return 'INVALID SCAN WEIGHT [NEGATIVE]: ' . $value;
            }
            if (!$allowZero && $value === 0) {
                return 'INVALID SCAN WEIGHT [ZERO]: ' . $value;
            }
            if ($value > $maxValue) {
                return 'INVALID SCAN WEIGHT [GREATER THAN 100]: ' . $value;
            }
        }
        return null;
    }


    private function migrateNewScanMeasurements()
    {
        $this->missingAnimalUlns = [];

        $weightRepository = $this->em->getRepository(Weight::class);
        $bodyFatRepository = $this->em->getRepository(BodyFat::class);
        $muscleThicknessRepository = $this->em->getRepository(MuscleThickness::class);

        $this->newWeightsCount = 0;
        $this->newBodyFatsCount = 0;
        $this->newMuscleThicknessesCount = 0;
        $this->batchCount = 0;
        $this->processedCount = 0;
        $this->skippedWeightCount = 0;
        $this->skippedMuscleThicknessCount = 0;
        $this->skippedBodyFatCount = 0;

        $developer = $this->getDeveloper();

        $recordsToProcess = count($this->data) - $this->currentKey;
        $this->cmdUtil->setStartTimeAndPrintIt($recordsToProcess, 1);

        foreach ($this->data as $key => $record) {
            if ($key < $this->currentKey) {
                continue;
            }

            $this->currentKey = $key;
            $ulnCountryCode = trim(strtoupper($record[0]));
            $ulnNumber = trim($record[1]);
            // $ubn = $record[2]; ignored
            $measurementDateString = trim($record[3]) .' 00:00:00';
            $scanWeight = $record[4];
            $fat1 = $record[5];
            $fat2 = $record[6];
            $fat3 = $record[7];
            $muscleThicknessValue = $record[8];
            $inspectorFullName = trim($record[9]);

            $ulnString = $ulnCountryCode . $ulnNumber;

            $animal = $this->findAnimalByUlnInAnimalsAndTagReplaceTables($ulnCountryCode, $ulnNumber);
            if (!$animal) {
                $this->missingAnimalUlns[$ulnString] = $ulnString;
                $this->advanceProgressBar();
                continue;
            }

            $inspector = $this->getInspectorByFullname($inspectorFullName);

            $measurementDate = \DateTime::createFromFormat(SqlUtil::DATE_TIME_FORMAT, $measurementDateString);
            if ($measurementDate === null) {
                throw new \Exception('EMPTY MEASUREMENT DATE! ' . $measurementDateString);
            }

            $animalIdAndDate = Measurement::generateAnimalIdAndDate($animal, $measurementDate);

            if ($scanWeight) {
                if ($weightRepository->findByAnimalAndDate($animal, $measurementDate)->count() === 0) {
                    $weight = new Weight();
                    $weight->setAnimal($animal);
                    $weight->setMeasurementDate($measurementDate);
                    $weight->setWeight($scanWeight);
                    $weight->setInspector($inspector);
                    $weight->setIsBirthWeight(false);
                    $weight->setAnimalIdAndDate($animalIdAndDate);
                    $weight->setActionBy($developer);

                    $this->em->persist($weight);
                    $this->newWeightsCount++;
                    $this->batchCount++;
                } else {
                    $this->skippedWeightCount++;
                }
            }

            if ($fat1 && $fat2 && $fat3
            && $fat1 !== '' && $fat2 !== '' && $fat3 !== '') {
                if ($bodyFatRepository->findByAnimalAndDate($animal, $measurementDate)->count() === 0) {
                    $fat1record = (new Fat1())->setFat($fat1);
                    $fat2record = (new Fat2())->setFat($fat2);
                    $fat3record = (new Fat3())->setFat($fat3);

                    $bodyFat = new BodyFat();
                    $bodyFat->setAnimal($animal);
                    $bodyFat->setMeasurementDate($measurementDate);
                    $bodyFat->setAnimalIdAndDate($animalIdAndDate);
                    $bodyFat->setInspector($inspector);
                    $bodyFat->setFat1($fat1record);
                    $bodyFat->setFat2($fat2record);
                    $bodyFat->setFat3($fat3record);
                    $bodyFat->setActionBy($developer);

                    $fat1record->setMeasurementDate($measurementDate);
                    $fat1record->setAnimalIdAndDate($animalIdAndDate);
                    $fat1record->setInspector($inspector);
                    $fat1record->setBodyFat($bodyFat);
                    $fat1record->setActionBy($developer);

                    $fat2record->setMeasurementDate($measurementDate);
                    $fat2record->setAnimalIdAndDate($animalIdAndDate);
                    $fat2record->setInspector($inspector);
                    $fat2record->setBodyFat($bodyFat);
                    $fat2record->setActionBy($developer);

                    $fat3record->setMeasurementDate($measurementDate);
                    $fat3record->setAnimalIdAndDate($animalIdAndDate);
                    $fat3record->setInspector($inspector);
                    $fat3record->setBodyFat($bodyFat);
                    $fat3record->setActionBy($developer);

                    $this->em->persist($fat1record);
                    $this->em->persist($fat2record);
                    $this->em->persist($fat3record);
                    $this->em->persist($bodyFat);
                    $this->newBodyFatsCount++;
                    $this->batchCount++;
                } else {
                    $this->skippedBodyFatCount++;
                }
            }

            if ($muscleThicknessValue) {
                if ($muscleThicknessRepository->findByAnimalAndDate($animal, $measurementDate)->count() === 0) {
                    $muscleThickness = new MuscleThickness();
                    $muscleThickness->setAnimal($animal);
                    $muscleThickness->setMeasurementDate($measurementDate);
                    $muscleThickness->setMuscleThickness($muscleThicknessValue);
                    $muscleThickness->setInspector($inspector);
                    $muscleThickness->setAnimalIdAndDate($animalIdAndDate);
                    $muscleThickness->setActionBy($developer);

                    $this->em->persist($muscleThickness);
                    $this->newMuscleThicknessesCount++;
                    $this->batchCount++;
                } else {
                    $this->skippedMuscleThicknessCount++;
                }
            }

            $this->flushBatch();

            $this->advanceProgressBar();
        }


        $this->flushBatch(true);

        $this->cmdUtil->setEndTimeAndPrintFinalOverview();

        $this->printMissingAnimals();
    }


    private function flushBatch($isFinalCheck = false)
    {
        if ($isFinalCheck || $this->batchCount >= self::BATCH_SIZE) {
            $this->em->flush();
            $this->processedCount += $this->batchCount;
            $this->batchCount = 0;
            $this->lastProcessedKey = $this->currentKey;
        }
    }


    private function advanceProgressBar()
    {
        $this->cmdUtil->advanceProgressBar(1, $this->getProgressBarMessage());
    }


    private function getProgressBarMessage()
    {
        return
            'last processed key ['.$this->lastProcessedKey.']'
            .'  weight|bodyFat|muscleThickness new[skipped]: '
            .$this->newWeightsCount.'['.$this->skippedWeightCount.']'.'|'
            .$this->newBodyFatsCount.'['.$this->skippedBodyFatCount.']'.'|'
            .$this->newMuscleThicknessesCount.'['.$this->skippedMuscleThicknessCount.']'
            .'  inBatch|Processed: '.$this->batchCount.'|'.$this->processedCount
            .'  missingAnimals: '.count($this->missingAnimalUlns);
    }


    private function printMissingAnimals()
    {
        if (count($this->missingAnimalUlns) === 0) {
            return;
        }

        $this->writeLn('====== MISSING ANIMALS ======');

        foreach ($this->missingAnimalUlns as $uln)
        {
            $this->writeLn($uln);
        }
    }


    /**
     * @param string $ulnCountryCode
     * @param string $ulnNumber
     * @return Animal|\AppBundle\Entity\Ewe|\AppBundle\Entity\Neuter|\AppBundle\Entity\Ram|null
     */
    private function findAnimalByUlnInAnimalsAndTagReplaceTables($ulnCountryCode, $ulnNumber)
    {
        $animal = $this->animalRepository->findByUlnCountryCodeAndNumber($ulnCountryCode, $ulnNumber);
        if ($animal) {
            return $animal;
        }

        return $this->em->getRepository(DeclareTagReplace::class)->getAnimalByNewestReplacementUln($ulnCountryCode.$ulnNumber, false);
    }
}