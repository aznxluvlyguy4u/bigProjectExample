<?php

namespace AppBundle\Command;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\MeasurementConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\BodyFat;
use AppBundle\Entity\BodyFatRepository;
use AppBundle\Entity\Inspector;
use AppBundle\Entity\InspectorRepository;
use AppBundle\Entity\MuscleThickness;
use AppBundle\Entity\MuscleThicknessRepository;
use AppBundle\Entity\Weight;
use AppBundle\Entity\WeightRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\MeasurementsUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\NumberUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class NsfoMigrateMeasurements2016v2Command extends ContainerAwareCommand
{
    const TITLE = 'MigrateMeasurements2016v2';
    const INPUT_PATH = '/path/to/file.txt';
    const IS_GROUPED_BY_ANIMAL_AND_DATE = true;
    const FOLDER_NAME = 'migration';
    const FILE_NAME_VSM_IDS_NOT_IN_DB = 'vsmId_van_metingen_waarvan_er_geen_dieren_in_de_database_zitten.txt';
    const DUPLICATE_MEASUREMENTS_ERROR_MESSAGE = 'There are duplicate measurements! Run duplicate measurements fix in nsfo:dump:mixblup option 5';
    const SEPARATOR = ';';
    const DUPLICATE_BODY_FATS_FILE_NAME = 'dubbele_vetmetingen.txt';
    const DUPLICATE_WEIGHTS_FILE_NAME = 'dubbele_gewichten.txt';
    const DUPLICATE_MUSCLE_THICKNESSES_FILE_NAME = 'dubbele_muscle_thicknesses.txt';
    const SOURCE_DB = 'database';
    const SOURCE_IMPORT_FILE = 'import_bestand';


    /** @var ObjectManager $em */
    private $em;

    /** @var AnimalRepository $animalRepository */
    private $animalRepository;

    /** @var InspectorRepository $inspectorRepository */
    private $inspectorRepository;

    /** @var WeightRepository $weightRepository */
    private $weightRepository;

    /** @var BodyFatRepository $bodyFatRepository */
    private $bodyFatRepository;

    /** @var MuscleThicknessRepository $muscleThicknessRepository */
    private $muscleThicknessRepository;

    /** @var array */
    private $idByAiindArray;

    /** @var array */
    private $weightsInDb;

    /** @var array */
    private $bodyFatsInDb;

    /** @var array */
    private $muscleThicknessesInDb;

    /** @var int */
    private $deletedDuplicates;

    /** @var int */
    private $fixedDuplicates;

    /** @var string */
    private $outputFolder;

    /** @var array */
    private $duplicateWeights;

    /** @var array */
    private $duplicateBodyFats;

    /** @var array */
    private $duplicateMuscleThicknesses;

    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_out' => 'app/Resources/outputs/',
        'finder_name' => '20160905_1040_Dierprestatietabel.csv',
        'ignoreFirstLine' => true
    );

    protected function configure()
    {
        $this
            ->setName('nsfo:migrate:measurements2016v2')
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

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $csv = $this->parseCSV();
        $totalNumberOfRows = sizeof($csv);

        $startUnit = 1;
        $startMessage = 'Retrieving search arrays...';
        $cmdUtil->setStartTimeAndPrintIt($totalNumberOfRows, $startUnit, $startMessage);

        //Create folders
        $this->outputFolder = $this->getContainer()->get('kernel')->getRootDir().'/Resources/outputs/'.self::FOLDER_NAME;
        NullChecker::createFolderPathIfNull($this->outputFolder);

        //Set repositories
        $this->animalRepository = $this->em->getRepository(Animal::class);
        $this->inspectorRepository = $this->em->getRepository(Inspector::class);
        $this->weightRepository = $this->em->getRepository(Weight::class);
        $this->bodyFatRepository = $this->em->getRepository(BodyFat::class);
        $this->muscleThicknessRepository = $this->em->getRepository(MuscleThickness::class);

        //Generate search arrays
        $this->idByAiindArray = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();
        $isIncludeRevokedWeights = false;
        $this->weightsInDb = $this->weightRepository->getAllWeightsBySql(self::IS_GROUPED_BY_ANIMAL_AND_DATE, $isIncludeRevokedWeights);
        $this->bodyFatsInDb = $this->bodyFatRepository->getAllBodyFatsBySql(self::IS_GROUPED_BY_ANIMAL_AND_DATE);
        $this->muscleThicknessesInDb = $this->muscleThicknessRepository->getAllMuscleThicknessesBySql(self::IS_GROUPED_BY_ANIMAL_AND_DATE);

        //Result arrays
        $vsmIdsNotInDatabase = array();
        $this->duplicateWeights = array();
        $this->duplicateBodyFats = array();
        $this->duplicateMuscleThicknesses = array();

        //Counters
        $this->fixedDuplicates = 0;
        $this->deletedDuplicates = 0;
        $rowCount = 0;
        $weightsNew = 0;
        $weightsFixed = 0;
        $weightsSkipped = 0;
        $bodyFatsNew = 0;
        $bodyFatsFixed = 0;
        $bodyFatsSkipped = 0;
        $muscleThicknessesNew = 0;
        $muscleThicknessesFixed = 0;
        $muscleThicknessesSkipped = 0;


        foreach ($csv as $row)
        {
            $vsmId = $row[0];
            $measurementDateString = TimeUtil::flipDateStringOrder($row[1]);
            $inspectorName = $row[2];

            //Replace ',' decimals by '.'
            $weight = NumberUtil::replaceCommaByDot($row[7]);
            $fat1 = NumberUtil::replaceCommaByDot($row[8]);
            $fat2 = NumberUtil::replaceCommaByDot($row[9]);
            $fat3 = NumberUtil::replaceCommaByDot($row[10]);
            $muscleThickness = NumberUtil::replaceCommaByDot($row[11]);

            $animalIdAndDate = $this->writeAnimalIdAndDate($vsmId, $measurementDateString);

            //First null check animal
            if($animalIdAndDate == null) {
                $vsmIdsNotInDatabase[$vsmId] = $vsmId;

            } else {
                $inspectorId = $this->getInspectorIdAndCreateNewInspectorIfNotInDb($inspectorName);

                //Check weights
                if(NullChecker::floatIsNotZero($weight)) {
                    $weightResult = $this->processWeightMeasurement($animalIdAndDate, $weight, $inspectorId);

                    if($weightResult == null) {
                        $weightsSkipped++;
                    } elseif ($weightResult = [true, false]) {
                        $weightsNew++;
                    } elseif ($weightResult = [true, true]) {
                        $weightsFixed++;
                    }
                }

                //Check BodyFats
                if(NullChecker::floatIsNotZero($fat1) && NullChecker::floatIsNotZero($fat2) && NullChecker::floatIsNotZero($fat3)) {
                    $bodyFatResult = $this->processBodyFatMeasurement($animalIdAndDate, $fat1, $fat2, $fat3, $inspectorId);

                    if($bodyFatResult == null) {
                        $bodyFatsSkipped++;
                    } elseif ($bodyFatResult = [true, false]) {
                        $bodyFatsNew++;
                    } elseif ($bodyFatResult = [true, true]) {
                        $bodyFatsFixed++;
                    }
                }

                //Check MuscleThicknesses
                if(NullChecker::floatIsNotZero($muscleThickness)) {
                    $muscleThicknessResult = $this->processMuscleThicknessMeasurement($animalIdAndDate, $muscleThickness, $inspectorId);

                    if($muscleThicknessResult == null) {
                        $muscleThicknessesSkipped++;
                    } elseif ($muscleThicknessResult = [true, false]) {
                        $muscleThicknessesNew++;
                    } elseif ($muscleThicknessResult = [true, true]) {
                        $muscleThicknessesFixed++;
                    }
                }
            }

            $message = $rowCount.' Weights : '.$weightsSkipped.'/'.$weightsNew.'/'.$weightsFixed
                                .' | BodyFats: '.$bodyFatsSkipped.'/'.$bodyFatsNew.'/'.$bodyFatsFixed
                                .' | MuscleThicknesses: '.$muscleThicknessesSkipped.'/'.$muscleThicknessesNew.'/'.$muscleThicknessesFixed
                                .' (skipped/new/updated) '.$this->deletedDuplicates.'/'.$this->fixedDuplicates.' (deleted-/fixed duplicates)'
                        ;
            $cmdUtil->advanceProgressBar(1, $message);
        }

        //Printing Errors
        foreach ($vsmIdsNotInDatabase as $vsmId) {
            file_put_contents($this->outputFolder.'/'.self::FILE_NAME_VSM_IDS_NOT_IN_DB, $vsmId."\n", FILE_APPEND);
        }

        /* Print duplicates */

        //column header
        file_put_contents($this->outputFolder.'/'.self::DUPLICATE_MUSCLE_THICKNESSES_FILE_NAME,
        'uln'.self::SEPARATOR.'stn'.self::SEPARATOR.'vsmId'.self::SEPARATOR.'meetdatum'
        .self::SEPARATOR.'fat1'.self::SEPARATOR.'fat2'.self::SEPARATOR.'fat3'.self::SEPARATOR.'inspector'.self::SEPARATOR.'bron'
        ."\n", FILE_APPEND);
        foreach ($this->duplicateBodyFats as $row) {
            file_put_contents($this->outputFolder.'/'.self::DUPLICATE_BODY_FATS_FILE_NAME, $row."\n", FILE_APPEND);
        }

        //column header
        file_put_contents($this->outputFolder.'/'.self::DUPLICATE_MUSCLE_THICKNESSES_FILE_NAME,
            'uln'.self::SEPARATOR.'stn'.self::SEPARATOR.'vsmId'.self::SEPARATOR.'meetdatum'
            .self::SEPARATOR.'muscle_thickness'.self::SEPARATOR.'inspector'.self::SEPARATOR.'bron'
        ."\n", FILE_APPEND);
        foreach ($this->duplicateMuscleThicknesses as $row) {
            file_put_contents($this->outputFolder.'/'.self::DUPLICATE_MUSCLE_THICKNESSES_FILE_NAME, $row."\n", FILE_APPEND);
        }

        //column header
        file_put_contents($this->outputFolder.'/'.self::DUPLICATE_MUSCLE_THICKNESSES_FILE_NAME,
            'uln'.self::SEPARATOR.'stn'.self::SEPARATOR.'vsmId'.self::SEPARATOR.'meetdatum'.self::SEPARATOR.'geboortegewicht'
            .self::SEPARATOR.'gewicht'.self::SEPARATOR.'inspector'.self::SEPARATOR.'bron'
        ."\n", FILE_APPEND);
        foreach ($this->duplicateWeights as $row) {
            file_put_contents($this->outputFolder.'/'.self::DUPLICATE_WEIGHTS_FILE_NAME, $row."\n", FILE_APPEND);
        }

        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }

    /**
     * @param int $vsmId
     * @param string $measurementDateString
     * @return string
     */
    private function writeAnimalIdAndDate($vsmId, $measurementDateString)
    {
        $id = Utils::getNullCheckedArrayValue($vsmId, $this->idByAiindArray);
        
        if($id != null) {
            return $id.'_'.$measurementDateString;
        } else {
            return null;
        }
    }


    /**
     * @param string $animalIdAndDate
     * @param float $weight
     * @param int $inspectorId
     * @return null|boolean
     */
    private function processWeightMeasurement($animalIdAndDate, $weight, $inspectorId)
    {
        if(NullChecker::isNull($animalIdAndDate) || NullChecker::numberIsNull($weight)) { return null; }

        $parts = MeasurementsUtil::getIdAndDateFromAnimalIdAndDateString($animalIdAndDate);
        $animalId = $parts[MeasurementConstant::ANIMAL_ID];
        $measurementDateString = $parts[MeasurementConstant::DATE];

        $weightsInDb = Utils::getNullCheckedArrayValue($animalIdAndDate, $this->weightsInDb);

        //BirthWeightCheck
        $isDateOfBirth = $this->animalRepository->isDateOfBirth($animalId, $measurementDateString);
        if($isDateOfBirth === null) {
            //Date format is incorrect, so it is highly likely that the measurementDate is missing
            //Skip measurement if measurementDate is missing.
            return null;

        } else {
            if ($isDateOfBirth) {
                if (!MeasurementsUtil::isValidBirthWeightValue($weight)) {
                    //Block birthWeights higher than 10 kg
                    return null;
                }
            }
        }
        //End of birthWeightCheck


        if($weightsInDb == null) {
            //No measurements found in db
            //Persist new measurement

            $this->weightRepository->insertNewWeight($animalIdAndDate, $weight, $inspectorId, $isDateOfBirth);
            return [true, false]; //successful insert

        } elseif(count($weightsInDb) > 1) {

            //base
            $weightInDb = $weightsInDb[0];
            $base = $weightInDb['uln'].self::SEPARATOR
                .$weightInDb['stn'].self::SEPARATOR
                .$weightInDb['vsm_id'].self::SEPARATOR
                .$measurementDateString.self::SEPARATOR
                .$weightInDb['is_birth_weight'];
            
            //write measurements in the db
            foreach($weightsInDb as $weightInDb) {
                $this->duplicateWeights[] = $base.self::SEPARATOR
                                            .$weightInDb['weight'].self::SEPARATOR
                                            .$weightInDb['inspector_last_name'].self::SEPARATOR
                                            .self::SOURCE_DB;
            }
            
            //write measurement from import file
            $inspectorLastName = $this->inspectorRepository->getLastNameById($inspectorId);
            $this->duplicateWeights[] = $base.self::SEPARATOR
                                        .$weight.self::SEPARATOR
                                        .$inspectorLastName.self::SEPARATOR
                                        .self::SOURCE_IMPORT_FILE;

        } else {
            $weightInDb = $weightsInDb[0];
            return $this->updateWeightIfDifferentInDb($inspectorId, $weightInDb, $weight);
        }
    }


    /**
     * @param int $inspectorId
     * @param array $weightInDb
     * @param float $newWeight
     * @return array|null
     */
    private function updateWeightIfDifferentInDb($inspectorId, $weightInDb, $newWeight)
    {
        //The birthWeight check must be done before the logic below

        $weightValueInDb = $weightInDb['weight'];
        $weightId = $weightInDb['id'];

        $isUpdateInspector = $this->updateInspectorIdIfNotEqual($weightId, $weightInDb['inspector_id'], $inspectorId);
        $isUpdateWeight = $weightValueInDb != $newWeight;
        if($isUpdateWeight) {
            //Update the db value with the value in the import file
            $sql = "UPDATE weight SET weight = '".$newWeight."' WHERE id = ".$weightId;
            $this->em->getConnection()->exec($sql);
        }
        //If they are equal, do nothing

        if($isUpdateInspector || $isUpdateWeight) {
            return [true, true]; //successful update
        } else {
            return null; //no changes
        }
    }


    /**
     * @param string $animalIdAndDate
     * @param float $fat1
     * @param float $fat2
     * @param float $fat3
     * @param int $inspectorId
     * @return null
     */
    private function processBodyFatMeasurement($animalIdAndDate, $fat1, $fat2, $fat3, $inspectorId)
    {
        $parts = MeasurementsUtil::getIdAndDateFromAnimalIdAndDateString($animalIdAndDate);
        $animalId = $parts[MeasurementConstant::ANIMAL_ID];
        $measurementDateString = $parts[MeasurementConstant::DATE];

        $bodyFatsInDb = Utils::getNullCheckedArrayValue($animalIdAndDate, $this->bodyFatsInDb);
        if($bodyFatsInDb == null) {
            //No measurements found in db
            //Persist new measurement
            $this->bodyFatRepository->insertNewBodyFat($animalIdAndDate, $fat1, $fat2, $fat3, $inspectorId);
            return null;

        } else {
            if(count($bodyFatsInDb)>1) {
                //base
                $bodyFatInDb = $bodyFatsInDb[0];
                $base = $bodyFatInDb['uln'].self::SEPARATOR
                    .$bodyFatInDb['stn'].self::SEPARATOR
                    .$bodyFatInDb['vsm_id'].self::SEPARATOR
                    .$measurementDateString.self::SEPARATOR;

                //write measurements in the db
                foreach($bodyFatsInDb as $bodyFatInDb) {
                    $this->duplicateBodyFats[] = $base.self::SEPARATOR
                        .$bodyFatInDb['fat1'].self::SEPARATOR
                        .$bodyFatInDb['fat2'].self::SEPARATOR
                        .$bodyFatInDb['fat3'].self::SEPARATOR
                        .$bodyFatInDb['inspector_last_name'].self::SEPARATOR
                        .self::SOURCE_DB;
                }

                //write measurement from import file
                $inspectorLastName = $this->inspectorRepository->getLastNameById($inspectorId);
                $this->duplicateWeights[] = $base.self::SEPARATOR
                    .$fat1.self::SEPARATOR
                    .$fat2.self::SEPARATOR
                    .$fat3.self::SEPARATOR
                    .$inspectorLastName.self::SEPARATOR
                    .self::SOURCE_IMPORT_FILE;

            } else {
                $bodyFatInDb = $bodyFatsInDb[0];
                $bodyFatId = $bodyFatInDb['id'];
                $fat1ValueInDb = $bodyFatInDb['fat1'];
                $fat2ValueInDb = $bodyFatInDb['fat2'];
                $fat3ValueInDb = $bodyFatInDb['fat3'];

                $isUpdateInspector = $this->updateInspectorIdIfNotEqual($bodyFatId, $bodyFatInDb['inspector_id'], $inspectorId);

                $isUpdateFat1 = $fat1ValueInDb != $fat1;
                if($isUpdateFat1) {
                    $sql = "UPDATE fat1 SET fat = '".$fat1."' WHERE id = ".$bodyFatInDb['fat1_id'];
                    $this->em->getConnection()->exec($sql);
                }

                $isUpdateFat2 = $fat2ValueInDb != $fat2;
                if($isUpdateFat2) {
                    $sql = "UPDATE fat2 SET fat = '".$fat2."' WHERE id = ".$bodyFatInDb['fat2_id'];
                    $this->em->getConnection()->exec($sql);
                }

                $isUpdateFat3 = $fat3ValueInDb != $fat3;
                if($isUpdateFat3) {
                    $sql = "UPDATE fat3 SET fat = '".$fat3."' WHERE id = ".$bodyFatInDb['fat3_id'];
                    $this->em->getConnection()->exec($sql);
                }
                

                if($isUpdateInspector || $isUpdateFat1 || $isUpdateFat2 || $isUpdateFat3) {
                    return [true, true]; //successful update
                } else {
                    return null; //no changes
                }
            }

        }
    }


    /**
     * @param string $animalIdAndDate
     * @param float $muscleThickness
     * @param int $inspectorId
     * @return null
     */
    private function processMuscleThicknessMeasurement($animalIdAndDate, $muscleThickness, $inspectorId)
    {
        $parts = MeasurementsUtil::getIdAndDateFromAnimalIdAndDateString($animalIdAndDate);
        $measurementDateString = $parts[MeasurementConstant::DATE];
        
        $muscleThicknessesInDb = Utils::getNullCheckedArrayValue($animalIdAndDate, $this->muscleThicknessesInDb);
        if($muscleThicknessesInDb == null) {
            //No measurements found in db
            //Persist new measurement
            $this->muscleThicknessRepository->insertNewMuscleThickness($animalIdAndDate, $muscleThickness, $inspectorId);

        } else {
            if(count($muscleThicknessesInDb)>1){
                //base
                $muscleThicknessInDb = $muscleThicknessesInDb[0];
                $base = $muscleThicknessInDb['uln'].self::SEPARATOR
                    .$muscleThicknessInDb['stn'].self::SEPARATOR
                    .$muscleThicknessInDb['vsm_id'].self::SEPARATOR
                    .$measurementDateString.self::SEPARATOR;

                //write measurements in the db
                foreach($muscleThicknessesInDb as $muscleThicknessInDb) {
                    $this->duplicateBodyFats[] = $base.self::SEPARATOR
                        .$muscleThicknessInDb['muscle_thickness'].self::SEPARATOR
                        .$muscleThicknessInDb['inspector_last_name'].self::SEPARATOR
                        .self::SOURCE_DB;
                }

                //write measurement from import file
                $inspectorLastName = $this->inspectorRepository->getLastNameById($inspectorId);
                $this->duplicateWeights[] = $base.self::SEPARATOR
                    .$muscleThickness.self::SEPARATOR
                    .$inspectorLastName.self::SEPARATOR
                    .self::SOURCE_IMPORT_FILE;

            } else {
                $muscleThicknessInDb = $muscleThicknessesInDb[0];
                $muscleThicknessValueInDb = $muscleThicknessInDb['muscle_thickness'];
                $muscleThicknessId = $muscleThicknessInDb['id'];

                $isUpdateInspector = $this->updateInspectorIdIfNotEqual($muscleThicknessId, $muscleThicknessInDb['inspector_id'], $inspectorId);
                $isUpdateMuscleThickness = $muscleThicknessValueInDb != $muscleThickness;

                if($isUpdateMuscleThickness) {
                    //Update the db value with the value in the import file
                    $sql = "UPDATE muscle_thickness SET muscle_thickness = '".$muscleThickness."' WHERE id = ".$muscleThicknessId;
                    $this->em->getConnection()->exec($sql);
                }
                //If they are equal, do nothing

                if($isUpdateInspector || $isUpdateMuscleThickness) {
                    return [true, true]; //successful update
                } else {
                    return null; //no changes
                }
            }

        }
    }


    /**
     * @param $measurementId
     * @param $inspectorIdInDb
     * @param $newInspectorId
     * @return boolean
     */
    private function updateInspectorIdIfNotEqual($measurementId, $inspectorIdInDb, $newInspectorId)
    {
        if($inspectorIdInDb != $newInspectorId) {
            if($newInspectorId == null) { $newInspectorId = 'NULL'; }

            //Update the db value with the value in the import file
            $sql = "UPDATE measurement SET inspector_id = ".$newInspectorId." WHERE id = ".$measurementId;
            $this->em->getConnection()->exec($sql);
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param string $inspectorName
     * @return int|null
     */
    private function getInspectorIdAndCreateNewInspectorIfNotInDb($inspectorName)
    {
        if(NullChecker::isNotNull($inspectorName)) {
            $inspectorId = $this->inspectorRepository->findFirstIdByLastName($inspectorName);
            if($inspectorId == null) {
                $firstName = '';
                $lastName = $inspectorName;
                $this->inspectorRepository->insertNewInspector($firstName, $lastName);
                $inspectorId = $this->inspectorRepository->findFirstIdByLastName($inspectorName);
            }
            return $inspectorId;
            
        } else {
            return null;
        }
    }
    

    private function parseCSV() {
        $ignoreFirstLine = $this->csvParsingOptions['ignoreFirstLine'];

        $finder = new Finder();
        $finder->files()
            ->in($this->csvParsingOptions['finder_in'])
            ->name($this->csvParsingOptions['finder_name'])
        ;
        foreach ($finder as $file) { $csv = $file; }

        $rows = array();
        if (($handle = fopen($csv->getRealPath(), "r")) !== FALSE) {
            $i = 0;
            while (($data = fgetcsv($handle, null, ";")) !== FALSE) {
                $i++;
                if ($ignoreFirstLine && $i == 1) { continue; }
                $rows[] = $data;
                gc_collect_cycles();
            }
            fclose($handle);
        }

        return $rows;
    }
}
