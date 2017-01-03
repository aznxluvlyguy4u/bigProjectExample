<?php


namespace AppBundle\Migration;


use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\AnimalResidenceRepository;
use AppBundle\Entity\Inspector;
use AppBundle\Entity\InspectorRepository;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class ExteriorMeasurementsMigrator
 *
 * There is a high risk that the data will become corrupted.
 *
 * @ORM\Entity(repositoryClass="AppBundle\Migration")
 * @package AppBundle\Migration
 */
class ExteriorMeasurementsMigrator extends MigratorBase
{
    const DEFAULT_START_ROW = 0;

    /** @var array */
    private $animalIdsByVsmIds;

    /** @var AnimalResidenceRepository */
    private $animalResidenceRepository;

    /**
     * AnimalResidenceMigrator constructor.
     * @param CommandUtil $cmdUtil
     * @param ObjectManager $em
     * @param OutputInterface $output
     * @param array $data
     */
    public function __construct(CommandUtil $cmdUtil, ObjectManager $em, OutputInterface $output, array $data)
    {
        parent::__construct($cmdUtil, $em, $output, $data);
        $this->animalResidenceRepository = $em->getRepository(AnimalResidence::class);
    }

    public function migrate()
    {
        $startCounter = $this->cmdUtil->generateQuestion('Please enter start row (default = '.self::DEFAULT_START_ROW.')', self::DEFAULT_START_ROW);
        $this->output->writeln([$startCounter.' <- chosen']);

        $this->output->writeln('=== Create search arrays ===');
        
        $inspectorIdsInDbByFullName = $this->createNewInspectorsIfMissingAndReturnLatestInspectorIds($this->data);
        $this->animalIdsByVsmIds = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();
        $exteriorCheckStringsByAnimalIdAndDate = $this->getCurrentExteriorMeasurementsSearchArray();
        
        $totalNumberOfRows = sizeof($this->data);
        $this->output->writeln('=== Migrating exteriorMeasurements ===');
        $this->cmdUtil->setStartTimeAndPrintIt($totalNumberOfRows, $startCounter);

        $incompleteCount = 0;
        $newCount = 0;
        $skippedCount = 0;
        for($i = $startCounter; $i < $totalNumberOfRows; $i++) {

            $record = $this->data[$i];
            
            $vsmId = $record[0];
            $measurementDate = TimeUtil::fillDateStringWithLeadingZeroes($record[1]);
            $animalId = ArrayUtil::get($vsmId, $this->animalIdsByVsmIds);

            $kind = $record[2];
            $skull = $record[3];
            $progress = $record[4];
            $muscularity = $record[5];
            $proportion = $record[6];
            $exteriorType = $record[7];
            $legWork = $record[8];
            $fur = $record[9];
            $generalAppearance = $record[10];
            $height = $record[11];
            $breastDepth = $record[12];
            $torsoLength = $record[13];
            $inspectorName = $record[14];


            $values = $skull.$muscularity.$proportion.$exteriorType.$legWork.$fur
                .$generalAppearance.$height.$breastDepth.$torsoLength.$kind.$progress;
            $areAllValuesEmpty = trim($values) == '';

            if($measurementDate == null || $measurementDate == '' || $animalId == null || $areAllValuesEmpty) {
                $incompleteCount++;
                $this->cmdUtil->advanceProgressBar(1, 'Exteriors new|skipped|incomplete: '.$newCount.'|'.$skippedCount.'|'.$incompleteCount);
                continue;
            }
            
            $checkStringCsv = $animalId.$measurementDate.$values;
            $animalIdAndDate = $animalId.'_'.$measurementDate;

            $checkStringInDb = ArrayUtil::get($animalIdAndDate, $exteriorCheckStringsByAnimalIdAndDate);
            $inspectorId = ArrayUtil::get($inspectorName, $inspectorIdsInDbByFullName);

            if($checkStringInDb == null) {
                //Record is empty

            } elseif($checkStringInDb != $checkStringCsv) {
                //Record needs to be updated

            }

            $this->cmdUtil->advanceProgressBar(1, 'Exteriors new|skipped|incomplete: '.$newCount.'|'.$skippedCount.'|'.$incompleteCount);
        }

        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    /**
     * @param array $csv
     * @return array
     */
    private function createNewInspectorsIfMissingAndReturnLatestInspectorIds(array $csv)
    {
        $this->output->writeln('Retrieving current inspectorIds by fullName from database ...');
        $inspectorIdsInDbByFullName = $this->getInspectorIdByFullNameSearchArray();

        $this->output->writeln('Getting all the inspectorNames from the csv file ...');
        $inspectors = [];
        foreach ($csv as $record) {
            $inspectors[$record[14]] = $record[14];
        }

        ksort($inspectors);
        //Remove blank inspectors

        foreach (['', ' '] as $blankName) {
            if(array_key_exists($blankName, $inspectors))  { unset($inspectors[$blankName]); }
        }

        foreach ($inspectors as $inspector) {
            if(array_key_exists($inspector, $inspectorIdsInDbByFullName)) {
                unset($inspectors[$inspector]);
            }
        }

        $missingInspectorCount = count($inspectors);
        if($missingInspectorCount > 0) {
            $this->output->writeln($missingInspectorCount.' inspectors are new and are going to be created now...');

            /** @var InspectorRepository $repository */
            $repository = $this->em->getRepository(Inspector::class);

            $failedInsertCount = 0;
            foreach ($inspectors as $inspectorName) {
                $firstName = '';
                $lastName = $inspectorName;
                $isInsertSuccessful = $repository->insertNewInspector($firstName, $lastName);
                if($isInsertSuccessful) {
                    $this->output->writeln($inspectorName.' : created as inspector');
                } else {
                    $this->output->writeln($inspectorName.' : INSERT FAILED');
                    $failedInsertCount++;
                }
            }

            if($failedInsertCount > 0) {
                $this->output->writeln($failedInsertCount.' : INSERTS FAILED, FIX THIS ISSUE');
                die;
            }

            $inspectorIdsInDbByFullName = $this->getInspectorIdByFullNameSearchArray();
        } else {
            $this->output->writeln('There are no missing inspectors');
        }

        return $inspectorIdsInDbByFullName;
    }


    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getInspectorIdByFullNameSearchArray()
    {
        $sql = "SELECT id, TRIM(CONCAT(first_name,' ', last_name)) as full_name
                FROM person WHERE type = 'Inspector'
                ORDER BY full_name ASC";
        $results = $this->conn->query($sql)->fetchAll();

        $inspectorIdsInDbByFullName = [];
        foreach ($results as $result) {
            $inspectorIdsInDbByFullName[$result['full_name']] = $result['id'];
        }

        return $inspectorIdsInDbByFullName;
    }


    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getCurrentExteriorMeasurementsSearchArray()
    {
        $sql = "SELECT m.inspector_id, DATE(m.measurement_date) as measurement_date, m.animal_id_and_date, x.*,
                  CONCAT(animal_id, DATE(m.measurement_date), skull, muscularity, proportion, exterior_type, leg_work, fur, general_appearence, height, breast_depth, torso_length, kind, progress) as check_string
                FROM exterior x
                INNER JOIN measurement m ON m.id = x.id";
        $results = $this->conn->query($sql)->fetchAll();
        
        $searchArray = [];
        foreach ($results as $result) {
            $animalIdAndDate = $result['animal_id_and_date'];
            $checkString = $result['check_string'];
            $searchArray[$animalIdAndDate] = $checkString;
        }
        
        return $searchArray;
    }
}