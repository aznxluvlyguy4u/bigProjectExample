<?php


namespace AppBundle\Migration;


use AppBundle\Component\Utils;
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
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class PerformanceMeasurementsMigrator extends MigratorBase
{
    const IS_GROUPED_BY_ANIMAL_AND_DATE = true;
    const CONFIRM_DELETE_DUPLICATES = true;

    //File- and Folder Names
    const FOLDER_NAME = 'migration';
    const FILE_NAME_VSM_IDS_NOT_IN_DB = 'vsmId_van_metingen_waarvan_er_geen_dieren_in_de_database_zitten.txt';
    const FILE_NAME_INCORRECT_DATES = 'foute_datums.txt';
    const DUPLICATE_MEASUREMENTS_ERROR_MESSAGE = 'There are duplicate measurements! Run duplicate measurements fix in nsfo:dump:mixblup option 5';
    const DUPLICATE_BODY_FATS_FILE_NAME = 'dubbele_vetmetingen.txt';
    const DUPLICATE_WEIGHTS_FILE_NAME = 'dubbele_gewichten.txt';
    const DUPLICATE_MUSCLE_THICKNESSES_FILE_NAME = 'dubbele_muscle_thicknesses.txt';

    const SOURCE_DB = 'database';
    const SOURCE_IMPORT_FILE = 'import_bestand';

    const SEPARATOR = ';';

    //Results
    const SUCCESSFUL_INSERT = 'SUCCESSFUL_INSERT';
    const SUCCESSFUL_UPDATE = 'SUCCESSFUL_UPDATE';
    const SKIPPED = 'SKIPPED';
    const DUPLICATE = 'DUPLICATE';
    
    /** @var InspectorRepository $inspectorRepository */
    private $inspectorRepository;

    /** @var WeightRepository $weightRepository */
    private $weightRepository;

    /** @var BodyFatRepository $bodyFatRepository */
    private $bodyFatRepository;

    /** @var MuscleThicknessRepository $muscleThicknessRepository */
    private $muscleThicknessRepository;
    
    /** @var string */
    private $rootDir;

    /** @var array */
    private $idByAiindArray;

    /** @var array */
    private $weightsInDb;

    /** @var array */
    private $bodyFatsInDb;

    /** @var array */
    private $muscleThicknessesInDb;

    /** @var boolean */
    private $isSuccessFull;

    /** @var MeasurementsFixer */
    private $measurementsFixer;

    /** @var string */
    private $outputFolder;

    /**
     * PerformanceMeasurementsMigrator constructor.
     * @param CommandUtil $cmdUtil
     * @param ObjectManager $em
     * @param array $data
     * @param string $rootDir
     * @param OutputInterface $outputInterface
     */
    public function __construct(CommandUtil $cmdUtil, ObjectManager $em, $data, $rootDir, OutputInterface $outputInterface)
    {
        parent::__construct($cmdUtil, $em, $outputInterface, $data);
        
        $this->rootDir = $rootDir;
        $this->outputFolder = $rootDir.'/Resources/outputs/migration';
        NullChecker::createFolderPathIfNull($this->outputFolder);

        //Initialize values
        $this->isSuccessFull = false;

        //Set repositories
        $this->inspectorRepository = $this->em->getRepository(Inspector::class);
        $this->weightRepository = $this->em->getRepository(Weight::class);
        $this->bodyFatRepository = $this->em->getRepository(BodyFat::class);
        $this->muscleThicknessRepository = $this->em->getRepository(MuscleThickness::class);

        $this->measurementsFixer = new MeasurementsFixer($em, $cmdUtil, $outputInterface);

        $this->createOutputFilesWithHeaders();
        $this->migratePerformanceMeasurements(self::CONFIRM_DELETE_DUPLICATES);
    }


    /**
     * @return bool
     */
    public function isSuccessFull()
    {
        return $this->isSuccessFull;
    }


    private function createOutputFilesWithHeaders()
    {
        //column headers of duplicate files
        file_put_contents($this->outputFolder.'/'.self::DUPLICATE_BODY_FATS_FILE_NAME,
            'uln'.self::SEPARATOR.'stn'.self::SEPARATOR.'vsmId'.self::SEPARATOR.'meetdatum'
            .self::SEPARATOR.'fat1'.self::SEPARATOR.'fat2'.self::SEPARATOR.'fat3'.self::SEPARATOR.'inspector'.self::SEPARATOR.'bron'
            ."\n", FILE_APPEND);

        file_put_contents($this->outputFolder.'/'.self::DUPLICATE_MUSCLE_THICKNESSES_FILE_NAME,
            'uln'.self::SEPARATOR.'stn'.self::SEPARATOR.'vsmId'.self::SEPARATOR.'meetdatum'
            .self::SEPARATOR.'muscle_thickness'.self::SEPARATOR.'inspector'.self::SEPARATOR.'bron'
            ."\n", FILE_APPEND);

        file_put_contents($this->outputFolder.'/'.self::DUPLICATE_WEIGHTS_FILE_NAME,
            'uln'.self::SEPARATOR.'stn'.self::SEPARATOR.'vsmId'.self::SEPARATOR.'meetdatum'.self::SEPARATOR.'geboortegewicht'
            .self::SEPARATOR.'gewicht'.self::SEPARATOR.'inspector'.self::SEPARATOR.'bron'
            ."\n", FILE_APPEND);
    }


    /**
     * @param bool $askConfirmationQuestionForDeletingDuplicateMeasurements
     */
    public function migratePerformanceMeasurements($askConfirmationQuestionForDeletingDuplicateMeasurements = false)
    {
        $startUnit = 1;
        $startMessage = 'Retrieving search arrays...';
        $this->cmdUtil->setStartTimeAndPrintIt(count($this->data), $startUnit, $startMessage);

        //Generate search arrays
        $this->idByAiindArray = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();
        $isIncludeRevokedWeights = false;
        $this->weightsInDb = $this->weightRepository->getAllWeightsBySql(self::IS_GROUPED_BY_ANIMAL_AND_DATE, $isIncludeRevokedWeights);
        $this->bodyFatsInDb = $this->bodyFatRepository->getAllBodyFatsBySql(self::IS_GROUPED_BY_ANIMAL_AND_DATE);
        $this->muscleThicknessesInDb = $this->muscleThicknessRepository->getAllMuscleThicknessesBySql(self::IS_GROUPED_BY_ANIMAL_AND_DATE);


        //Result arrays
        $vsmIdsNotInDatabase = array();
        $incorrectDates = array();

        //Counters
        $rowCount = 0;
        $weightsNew = 0;
        $weightsFixed = 0;
        $weightsSkipped = 0;
        $weightsDuplicates = 0;
        $bodyFatsNew = 0;
        $bodyFatsFixed = 0;
        $bodyFatsSkipped = 0;
        $bodyFatsDuplicates = 0;
        $muscleThicknessesNew = 0;
        $muscleThicknessesFixed = 0;
        $muscleThicknessesSkipped = 0;
        $muscleThicknessesDuplicates = 0;


        foreach ($this->data as $row)
        {
            $vsmId = $row[0];
            $measurementDateString = TimeUtil::fillDateStringWithLeadingZeroes($row[1]);
            $inspectorName = $row[2];

            //Replace ',' decimals by '.'
            $weight = NumberUtil::fixIncorrectDecimals($row[7]);
            $fat1 = NumberUtil::fixIncorrectDecimals($row[8]);
            $fat2 = NumberUtil::fixIncorrectDecimals($row[9]);
            $fat3 = NumberUtil::fixIncorrectDecimals($row[10]);
            $muscleThickness = NumberUtil::fixIncorrectDecimals($row[11]);

            //$animalId = $this->idByAiindArray[$vsmId];
            $animalIdAndDate = $this->writeAnimalIdAndDate($vsmId, $measurementDateString);

            //First null check animal
            if($animalIdAndDate == null) {
                $vsmIdsNotInDatabase[$vsmId] = $vsmId;
                if($measurementDateString == null) {
                    $incorrectDates[] = $row[1];
                }

            } else {
                $inspectorId = $this->getInspectorIdAndCreateNewInspectorIfNotInDb($inspectorName);

                //Check weights
                if(NullChecker::floatIsNotZero($weight)) {
                    $weightResult = $this->processWeightMeasurement($animalIdAndDate, $weight, $inspectorId);

                    if($weightResult == self::DUPLICATE) {
                        $weightsDuplicates++;
                    } elseif ($weightResult == self::SUCCESSFUL_INSERT) {
                        $weightsNew++;
                    } elseif ($weightResult == self::SUCCESSFUL_UPDATE) {
                        $weightsFixed++;
                    } else {
                        $weightsSkipped++;
                    }
                }

                //Check BodyFats
                if(NullChecker::floatIsNotZero($fat1) && NullChecker::floatIsNotZero($fat2) && NullChecker::floatIsNotZero($fat3)) {
                    $bodyFatResult = $this->processBodyFatMeasurement($animalIdAndDate, $fat1, $fat2, $fat3, $inspectorId);

                    if($bodyFatResult == self::DUPLICATE) {
                        $bodyFatsDuplicates++;
                    } elseif ($bodyFatResult == self::SUCCESSFUL_INSERT) {
                        $bodyFatsNew++;
                    } elseif ($bodyFatResult == self::SUCCESSFUL_UPDATE) {
                        $bodyFatsFixed++;
                    } else {
                        $bodyFatsSkipped++;
                    }
                }

                //Check MuscleThicknesses
                if(NullChecker::floatIsNotZero($muscleThickness)) {
                    $muscleThicknessResult = $this->processMuscleThicknessMeasurement($animalIdAndDate, $muscleThickness, $inspectorId);

                    if($muscleThicknessResult == self::DUPLICATE) {
                        $muscleThicknessesDuplicates++;
                    } elseif ($muscleThicknessResult == self::SUCCESSFUL_INSERT) {
                        $muscleThicknessesNew++;
                    } elseif ($muscleThicknessResult == self::SUCCESSFUL_UPDATE) {
                        $muscleThicknessesFixed++;
                    } else {
                        $muscleThicknessesSkipped++;
                    }
                }
            }

            $message = $rowCount.' Weights : '.$weightsSkipped.'/'.$weightsNew.'/'.$weightsFixed.'/'.$weightsDuplicates
                .' | B.Fats: '.$bodyFatsSkipped.'/'.$bodyFatsNew.'/'.$bodyFatsFixed.'/'.$bodyFatsDuplicates
                .' | Muscle.T: '.$muscleThicknessesSkipped.'/'.$muscleThicknessesNew.'/'.$muscleThicknessesFixed.'/'.$muscleThicknessesDuplicates
                .' (skipped/new/updated/double) noVsmId/date : '.count($vsmIdsNotInDatabase).'/'.count($incorrectDates);
            ;
            $this->cmdUtil->advanceProgressBar(1, $message);
        }

        //Printing Errors
        foreach ($vsmIdsNotInDatabase as $vsmId) {
            file_put_contents($this->outputFolder.'/'.self::FILE_NAME_VSM_IDS_NOT_IN_DB, $vsmId."\n", FILE_APPEND);
        }

        foreach ($incorrectDates as $incorrectDate) {
            file_put_contents($this->outputFolder.'/'.self::FILE_NAME_INCORRECT_DATES, $incorrectDate."\n", FILE_APPEND);
        }

        $this->cmdUtil->setEndTimeAndPrintFinalOverview();

        $this->measurementsFixer->deleteDuplicateMeasurements($askConfirmationQuestionForDeletingDuplicateMeasurements);
        
        $this->isSuccessFull = true;
    }

    /**
     * @param int $vsmId
     * @param string $measurementDateString
     * @return string
     */
    private function writeAnimalIdAndDate($vsmId, $measurementDateString)
    {
        $id = Utils::getNullCheckedArrayValue($vsmId, $this->idByAiindArray);

        if($id != null && $measurementDateString != null) {
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
        $isBirthWeight = $this->animalRepository->isWithin3DaysAfterDateOfBirth($animalId, $measurementDateString);
        if($isBirthWeight === null) {
            //Date format is incorrect, so it is highly likely that the measurementDate is missing
            //Skip measurement if measurementDate is missing.
            return null;

        } else {
            if ($isBirthWeight) {
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

            $this->weightRepository->insertNewWeight($animalIdAndDate, $weight, $inspectorId, $isBirthWeight);
            return self::SUCCESSFUL_INSERT; //successful insert

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
                $row = $base.self::SEPARATOR
                    .$weightInDb['weight'].self::SEPARATOR
                    .$weightInDb['inspector_last_name'].self::SEPARATOR
                    .self::SOURCE_DB;
                file_put_contents($this->outputFolder.'/'.self::DUPLICATE_WEIGHTS_FILE_NAME, $row."\n", FILE_APPEND);
            }

            //write measurement from import file
            $inspectorLastName = $this->inspectorRepository->getLastNameById($inspectorId);
            $row = $base.self::SEPARATOR
                .$weight.self::SEPARATOR
                .$inspectorLastName.self::SEPARATOR
                .self::SOURCE_IMPORT_FILE;
            file_put_contents($this->outputFolder.'/'.self::DUPLICATE_WEIGHTS_FILE_NAME, $row."\n", FILE_APPEND);

            return self::DUPLICATE;

        } else {
            $weightInDb = $weightsInDb[0];
            $isBirthWeightInDb = $weightInDb['is_birth_weight'];
            return $this->updateWeightIfDifferentInDb($inspectorId, $weightInDb, $weight, $isBirthWeightInDb, $isBirthWeight);
        }
    }


    /**
     * @param int $inspectorId
     * @param array $weightInDb
     * @param float $newWeight
     * @param boolean $isBirthWeightInDb
     * @param boolean $isBirthWeight
     * @return array|null
     */
    private function updateWeightIfDifferentInDb($inspectorId, $weightInDb, $newWeight, $isBirthWeightInDb, $isBirthWeight)
    {
        //The birthWeight check must be done before the logic below

        $weightValueInDb = $weightInDb['weight'];
        $weightId = $weightInDb['id'];

        $isUpdateInspector = $this->updateInspectorIdIfNotEqual($weightId, $weightInDb['inspector_id'], $inspectorId);
        $isBirthWeight = $isBirthWeight == null ? false : $isBirthWeight;
        $isUpdateWeight = $weightValueInDb != $newWeight || $isBirthWeightInDb != $isBirthWeight;
        if($isUpdateWeight) {
            $isBirthWeightString = StringUtil::getBooleanAsString($isBirthWeight);
            //Update the db value with the value in the import file
            $sql = "UPDATE weight SET weight = '".$newWeight."', is_birth_weight = ".$isBirthWeightString." WHERE id = ".$weightId;
            $this->em->getConnection()->exec($sql);
        }
        //If they are equal, do nothing

        if($isUpdateInspector || $isUpdateWeight) {
            return self::SUCCESSFUL_UPDATE; //successful update
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
                    $row = $base.self::SEPARATOR
                        .$bodyFatInDb['fat1'].self::SEPARATOR
                        .$bodyFatInDb['fat2'].self::SEPARATOR
                        .$bodyFatInDb['fat3'].self::SEPARATOR
                        .$bodyFatInDb['inspector_last_name'].self::SEPARATOR
                        .self::SOURCE_DB;
                    file_put_contents($this->outputFolder.'/'.self::DUPLICATE_BODY_FATS_FILE_NAME, $row."\n", FILE_APPEND);
                }

                //write measurement from import file
                $inspectorLastName = $this->inspectorRepository->getLastNameById($inspectorId);
                $row = $base.self::SEPARATOR
                    .$fat1.self::SEPARATOR
                    .$fat2.self::SEPARATOR
                    .$fat3.self::SEPARATOR
                    .$inspectorLastName.self::SEPARATOR
                    .self::SOURCE_IMPORT_FILE;
                file_put_contents($this->outputFolder.'/'.self::DUPLICATE_BODY_FATS_FILE_NAME, $row."\n", FILE_APPEND);

                return self::DUPLICATE;

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
                    return self::SUCCESSFUL_UPDATE; //successful update
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
                    $row = $base.self::SEPARATOR
                        .$muscleThicknessInDb['muscle_thickness'].self::SEPARATOR
                        .$muscleThicknessInDb['inspector_last_name'].self::SEPARATOR
                        .self::SOURCE_DB;
                    file_put_contents($this->outputFolder.'/'.self::DUPLICATE_MUSCLE_THICKNESSES_FILE_NAME, $row."\n", FILE_APPEND);
                }

                //write measurement from import file
                $inspectorLastName = $this->inspectorRepository->getLastNameById($inspectorId);
                $row = $base.self::SEPARATOR
                    .$muscleThickness.self::SEPARATOR
                    .$inspectorLastName.self::SEPARATOR
                    .self::SOURCE_IMPORT_FILE;
                file_put_contents($this->outputFolder.'/'.self::DUPLICATE_MUSCLE_THICKNESSES_FILE_NAME, $row."\n", FILE_APPEND);

                return self::DUPLICATE;

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
                    return self::SUCCESSFUL_UPDATE; //successful update
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


}