<?php

namespace AppBundle\Command;

use AppBundle\Component\Utils;
use AppBundle\Constant\MeasurementConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\BodyFat;
use AppBundle\Entity\BodyFatRepository;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\Inspector;
use AppBundle\Entity\InspectorRepository;
use AppBundle\Entity\MuscleThickness;
use AppBundle\Entity\MuscleThicknessRepository;
use AppBundle\Entity\TailLengthRepository;
use AppBundle\Entity\Weight;
use AppBundle\Entity\WeightRepository;
use AppBundle\Enumerator\MeasurementType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\MeasurementsUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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

    /** @var string */
    private $outputFolder;

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

        //Counters
        $rowCount = 0;
        $weightsNew = 0;
        $weightsFixed = 0;
        $weightsSkipped = 0;

        foreach ($csv as $row)
        {
            $vsmId = $row[0];
            $measurementDateString = TimeUtil::flipDateStringOrder($row[1]);
            $inspectorName = $row[2];

            $weight = $row[7];
            $fat1 = $row[8];
            $fat2 = $row[9];
            $fat3 = $row[10];
            $muscleThickness = $row[11];

            $animalIdAndDate = $this->writeAnimalIdAndDate($vsmId, $measurementDateString);

            //First null check animal
            if($animalIdAndDate == null) {
                $vsmIdsNotInDatabase[$vsmId] = $vsmId;

            } else {
                $inspectorId = $this->getInspectorIdAndCreateNewInspectorIfNotInDb($inspectorName);

                //Check weights
                if(NullChecker::floatIsNotZero($weight)) {
                    $this->processWeightMeasurement($animalIdAndDate, $weight, $inspectorId);
                }

                //Check BodyFats
                if(NullChecker::floatIsNotZero($fat1) && NullChecker::floatIsNotZero($fat2) && NullChecker::floatIsNotZero($fat3)) {
                    $this->processBodyFatMeasurement($animalIdAndDate, $fat1, $fat2, $fat3, $inspectorId);
                }

                //Check MuscleThicknesses
                if(NullChecker::floatIsNotZero($muscleThickness)) {
                    $this->processMuscleThicknessMeasurement($animalIdAndDate, $muscleThickness, $inspectorId);
                }
            }

            $message = $rowCount; //FIXME
            $cmdUtil->advanceProgressBar(1, $message);
        }

        //Printing Errors
        foreach ($vsmIdsNotInDatabase as $vsmId) {
            file_put_contents($this->outputFolder.'/'.self::FILE_NAME_VSM_IDS_NOT_IN_DB, $vsmId."\n", FILE_APPEND);
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
        if($weightsInDb == null) {
            //No measurements found in db
            //Persist new measurement
            $isDateOfBirth = $this->animalRepository->isDateOfBirth($animalId, $measurementDateString);
            if($isDateOfBirth === null) {
                //Date format is incorrect, so it is highly likely that the measurementDate is missing
                //Skip measurement if measurementDate is missing.
                return null;
                
            } else {
                if($isDateOfBirth) {
                    if(!MeasurementsUtil::isValidBirthWeightValue($weight)){
                        //Block birthWeights higher than 10 kg
                        return null;
                    }
                }
                $this->weightRepository->insertNewWeight($animalIdAndDate, $weight, $inspectorId, $isDateOfBirth);
                return true;
            }

        } else {
            if(count($weightsInDb) > 1) {
                dump(self::DUPLICATE_MEASUREMENTS_ERROR_MESSAGE);die;
            }
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
                dump(self::DUPLICATE_MEASUREMENTS_ERROR_MESSAGE);die;
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
//        $parts = MeasurementsUtil::getIdAndDateFromAnimalIdAndDateString($animalIdAndDate);
//        $animalId = $parts[MeasurementConstant::ANIMAL_ID];
//        $measurementDateString = $parts[MeasurementConstant::DATE];

        $muscleThicknessesInDb = Utils::getNullCheckedArrayValue($animalIdAndDate, $this->muscleThicknessesInDb);
        if($muscleThicknessesInDb == null) {
            //No measurements found in db
            //Persist new measurement
            $this->muscleThicknessRepository->insertNewMuscleThickness($animalIdAndDate, $muscleThickness, $inspectorId);

        } else {
            if(count($muscleThicknessesInDb)>1){
                dump(self::DUPLICATE_MEASUREMENTS_ERROR_MESSAGE);die;
            }

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
