<?php


namespace AppBundle\Migration;


use AppBundle\Cache\AnimalCacher;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\AnimalResidenceRepository;
use AppBundle\Entity\Inspector;
use AppBundle\Entity\InspectorRepository;
use AppBundle\Enumerator\MeasurementType;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\SqlUtil;
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
    const MAX_EXTERIOR_VALUE = 99;
    const MIN_EXTERIOR_VALUE = 69;
    const INSERT_BATCH_SIZE = 1000;

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
        $startCounter = $this->cmdUtil->generateQuestion('Please enter start row (default = ' . self::DEFAULT_START_ROW . ')', self::DEFAULT_START_ROW);
        $this->output->writeln([$startCounter . ' <- chosen']);

        $this->output->writeln('=== Create search arrays ===');

        $inspectorIdsInDbByFullName = $this->createNewInspectorsIfMissingAndReturnLatestInspectorIds($this->data);
        $this->animalIdsByVsmIds = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();
        $exteriorCheckStrings = $this->getCurrentExteriorMeasurementsSearchArray();

        $totalNumberOfRows = sizeof($this->data);
        $this->output->writeln('=== Migrating exteriorMeasurements ===');
        $this->deleteOrphanedMeasurements();
        $this->cmdUtil->setStartTimeAndPrintIt($totalNumberOfRows, $startCounter);

        $incompleteCount = 0;
        $newCount = 0;
        $batchCount = 0;
        $skippedCount = 0;
        $insertMeasurementString = '';
        $insertExteriorString = '';


        $markings = 0;
        $logDate = TimeUtil::getLogDateString();

        $sql = "SELECT MAX(id) as max_id FROM measurement";
        $maxMeasurementId = $this->conn->query($sql)->fetch()['max_id'];

        for ($i = $startCounter; $i < $totalNumberOfRows; $i++) {

            $record = $this->data[$i];

            $vsmId = $record[0];
            $measurementDate = TimeUtil::fillDateStringWithLeadingZeroes($record[1]);
            $animalId = ArrayUtil::get($vsmId, $this->animalIdsByVsmIds);

            $kind = $record[2];

            $skull = self::validateExteriorValue($record[3]);
            $progress = self::validateExteriorValue($record[4]);
            $muscularity = self::validateExteriorValue($record[5]);
            $proportion = self::validateExteriorValue($record[6]);
            $exteriorType = self::validateExteriorValue($record[7]);
            $legWork = self::validateExteriorValue($record[8]);
            $fur = self::validateExteriorValue($record[9]);
            $generalAppearance = self::validateExteriorValue($record[10]);
            $height = self::validateExteriorValue($record[11]);
            $breastDepth = self::validateExteriorValue($record[12]);
            $torsoLength = self::validateExteriorValue($record[13]);

            $inspectorName = $record[14];


            $values = $skull . $muscularity . $proportion . $exteriorType . $legWork . $fur
                . $generalAppearance . $height . $breastDepth . $torsoLength . $kind . $progress;
            $areAllValuesEmpty = trim($values, '0') == '';

            if ($measurementDate == null || $measurementDate == '' || $animalId == null || $areAllValuesEmpty) {
                $incompleteCount++;
                $this->cmdUtil->advanceProgressBar(1, 'Exteriors new|inBatch|skipped|incomplete: ' . $newCount .'|'.$batchCount. '|' . $skippedCount . '|' . $incompleteCount.' record: '.$i);
                continue;
            }

            $inspectorId = ArrayUtil::get($inspectorName, $inspectorIdsInDbByFullName);
            $animalIdAndDate = $animalId . '_' . $measurementDate;
            $checkStringCsv = $animalIdAndDate.$values.$inspectorId;

            if (!array_key_exists($checkStringCsv, $exteriorCheckStrings)) {
                //Record is empty
                $maxMeasurementId++;

                $insertExteriorString = $insertExteriorString . "(" . $maxMeasurementId . "," . $animalId . ",'" . $skull . "','" .
                    $muscularity. "','" . $proportion . "','" . $exteriorType . "','" . $legWork . "','" . $fur . "','" .
                    $generalAppearance . "','" . $height . "','" . $breastDepth . "','" . $torsoLength . "','" .
                    $markings . "','" . $kind . "','" . $progress . "'),";

                $insertMeasurementString = $insertMeasurementString . "(" . $maxMeasurementId . "," .
                    SqlUtil::getNullCheckedValueForSqlQuery($inspectorId, false) . ",'" . $logDate . "','" . $measurementDate . "','".MeasurementType::EXTERIOR."','" . $animalIdAndDate . "'),";

                $batchCount++;
                $exteriorCheckStrings[$checkStringCsv] = $checkStringCsv;

            } else {
                $skippedCount++;
            }


            if($batchCount%self::INSERT_BATCH_SIZE == 0 && $batchCount != 0) {
                $this->batchInsert($insertMeasurementString, $insertExteriorString);

                //Reset batch values AFTER insert
                $insertMeasurementString = '';
                $insertExteriorString = '';
                $newCount += $batchCount;
                $batchCount = 0;
            }

            $this->cmdUtil->advanceProgressBar(1, 'Exteriors new|inBatch|skipped|incomplete: ' . $newCount .'|'.$batchCount. '|' . $skippedCount . '|' . $incompleteCount.' record: '.$i);
        }

        if($batchCount != 0) {
            $this->batchInsert($insertMeasurementString, $insertExteriorString);

            //Reset batch values AFTER insert
            $insertMeasurementString = '';
            $insertExteriorString = '';
            $newCount += $batchCount;
            $batchCount = 0;
            $this->cmdUtil->advanceProgressBar(1, 'Exteriors new|inBatch|skipped|incomplete: ' . $newCount .'|'.$batchCount. '|' . $skippedCount . '|' . $incompleteCount.' record: '.$i);
        }


        $this->cmdUtil->setEndTimeAndPrintFinalOverview();

        //Update values in cache so that the imported values are actually shown in the pedigreeReports
        AnimalCacher::cacheExteriorsEqualOrOlderThanLogDate($this->em, $logDate, $this->cmdUtil);
    }


    /**
     * @param string $insertMeasurementString
     * @param string $insertExteriorString
     * @throws \Doctrine\DBAL\DBALException
     */
    private function batchInsert($insertMeasurementString, $insertExteriorString)
    {
        $insertExteriorString = rtrim($insertExteriorString,',');
        $insertMeasurementString = rtrim($insertMeasurementString,',');

        $sql = "INSERT INTO measurement (id, inspector_id, log_date, measurement_date, type, animal_id_and_date 
						)VALUES " . $insertMeasurementString;
        $this->conn->exec($sql);

        $sql = "INSERT INTO exterior (id, animal_id, skull, muscularity, proportion, exterior_type, leg_work, fur,
                        general_appearence, height, breast_depth, torso_length, markings, kind, progress
						)VALUES " . $insertExteriorString;
        $this->conn->exec($sql);
    }


    /**
     * @param $exteriorValue
     * @return float
     */
    public static function validateExteriorValue($exteriorValue)
    {
        if(self::MIN_EXTERIOR_VALUE <= $exteriorValue && $exteriorValue <= self::MAX_EXTERIOR_VALUE) {
            return floatval($exteriorValue);
        }
        return 0.0;
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

        /** @var InspectorRepository $repository */
        $repository = $this->em->getRepository(Inspector::class);

        $missingInspectorCount = count($inspectors);
        if($missingInspectorCount > 0) {
            $this->output->writeln($missingInspectorCount.' inspectors are new and are going to be created now...');

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

        $repository->fixMissingInspectorTableRecords();

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
                  CONCAT(animal_id, '_', DATE(m.measurement_date), skull, muscularity, proportion, exterior_type, leg_work, fur, general_appearence, height, breast_depth, torso_length, kind, progress, inspector_id) as check_string
                FROM exterior x
                INNER JOIN measurement m ON m.id = x.id";
        $results = $this->conn->query($sql)->fetchAll();
        
        $searchArray = [];
        foreach ($results as $result) {
            $animalIdAndDate = $result['animal_id_and_date'];
            $checkString = $result['check_string'];
            $searchArray[$checkString] = $checkString;
        }
        
        return $searchArray;
    }


    private function deleteOrphanedMeasurements()
    {
        $sql = "SELECT m.id FROM measurement m
                LEFT JOIN exterior x ON m.id = x.id
                WHERE m.type = 'Exterior' AND x.id ISNULL";
        $results = $this->conn->query($sql)->fetchAll();

        if(count($results) == 0) {
            $this->output->writeln('There are no deleted orphans measurement records!');
            return;
        }

        $filter = '';
        foreach ($results as $result) {
            $id = $result['id'];
            $filter = $filter.' id = '.$id;
        }

        $sql = "DELETE FROM measurement WHERE ".$filter;
        $this->conn->exec($sql);
        $this->output->writeln(count($results).' orphaned measurements deleted!');
    }
}