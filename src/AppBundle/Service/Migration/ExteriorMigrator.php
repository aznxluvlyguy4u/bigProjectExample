<?php


namespace AppBundle\Service\Migration;

use AppBundle\Cache\ExteriorCacher;
use AppBundle\Entity\Inspector;
use AppBundle\Entity\Measurement;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\QueryType;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\MeasurementsUtil;
use AppBundle\Util\NumberUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Validation\ExteriorValidator;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class ExteriorMigrator
 */
class ExteriorMigrator extends Migrator2017JunServiceBase implements IMigratorService
{
    /** @inheritdoc */
    public function __construct(ObjectManager $em, $rootDir)
    {
        parent::__construct($em, $rootDir);
    }

    /** @inheritDoc */
    function run(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        $this->writeLn('====== PRE migration fixes ======');
        $this->data = $this->parseCSV(self::EXTERIORS);
        $this->createInspectorSearchArrayAndInsertNewInspectors();
        MeasurementsUtil::generateAnimalIdAndDateValues($this->conn, false, $cmdUtil);

        $this->writeln('====== Migrate Exteriors ======');
        $this->migrateNewExteriors();

        $this->writeLn('====== POST migration updates ======');
        $this->cmdUtil->writeln( ExteriorCacher::updateAllExteriors($this->conn) . ' exterior cache records updated');
    }



    private function createInspectorSearchArrayAndInsertNewInspectors()
    {
        $this->writeLn('Creating inspector search Array ...');

        DoctrineUtil::updateTableSequence($this->conn, [Person::getTableName()]);

        $this->inspectorIdsInDbByFullName = $this->getInspectorSearchArrayWithNameCorrections();

        $newInspectors = [];

        foreach ($this->data as $record) {
            $inspectorFullName = $record[14];

            if ($inspectorFullName !== '' && !key_exists($inspectorFullName, $this->inspectorIdsInDbByFullName)
            && !key_exists($inspectorFullName, $newInspectors)) {
                $newInspectors[$inspectorFullName] = $inspectorFullName;
            }
        }

        if (count($newInspectors) === 0) {
            return;
        }

        $this->writeLn('Inserting '.count($newInspectors).' new inspectors ...');
        foreach ($newInspectors as $newInspectorFullName) {
            $nameParts = explode(' ', $newInspectorFullName);
            $inspector = new Inspector();
            $inspector
                ->setFirstName($nameParts[0])
                ->setLastName($nameParts[1])
                ->setPassword('BLANK')
            ;
            $this->em->persist($inspector);
            $this->writeLn($inspector->getFullName());
        }
        $this->em->flush();

        $this->writeln(count($newInspectors) . ' new inspectors inserted (without inspectorCode nor authorization');
    }


    private function migrateNewExteriors()
    {
        $this->writeLn('=== Migrating NEW exterior measurements ===');

        DoctrineUtil::updateTableSequence($this->conn, [Measurement::getTableName()]);

        $this->sqlBatchProcessor
            ->createBatchSet(QueryType::BASE_INSERT)
            ->createBatchSet(QueryType::INSERT)
            ;

        $baseInsertBatchSet = $this->sqlBatchProcessor->getSet(QueryType::BASE_INSERT);
        $insertBatchSet = $this->sqlBatchProcessor->getSet(QueryType::INSERT);

        $baseInsertBatchSet->setSqlQueryBase("INSERT INTO measurement (id, log_date, inspector_id, measurement_date, 
                                                              type, animal_id_and_date, action_by_id) VALUES ");

        $insertBatchSet->setSqlQueryBase("INSERT INTO exterior (id, animal_id, skull, muscularity, proportion, 
                                              exterior_type, leg_work, fur, general_appearance, height, breast_depth, 
                                              torso_length, kind, progress)  VALUES ");

        $id = SqlUtil::getMaxId($this->conn, Measurement::getTableName());
        $firstMaxId = $id + 1;

        $this->writeLn('Create animal_id by vsmId search array');
        $this->animalIdsByVsmId = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();


        $sql = "SELECT animal_id_and_date FROM measurement WHERE type = 'Exterior'";
        $results = $this->conn->query($sql)->fetchAll();
        $exteriorMeasurementAnimalIdAndDates = SqlUtil::getSingleValueGroupedSqlResults('animal_id_and_date',$results);


        $this->sqlBatchProcessor->start(count($this->data));

        $newExteriors = [];

        $logDate = TimeUtil::getLogDateString();

        try {

            foreach ($this->data as $record) {
                $vsmId = $record[0];
                $measurementDateString = TimeUtil::getTimeStampForSqlFromAnyDateString($record[1], false);
                $kind = $record[2];
                $skull = $this->getExteriorValue(3, $record);
                $progress = $this->getExteriorValue(4, $record);
                $muscularity = $this->getExteriorValue(5, $record);
                $proportion = $this->getExteriorValue(6, $record);
                $exteriorType = $this->getExteriorValue(7, $record);
                $legWork = $this->getExteriorValue(8, $record);
                $fur = $this->getExteriorValue(9, $record);
                $generalAppearance = $this->getExteriorValue(10, $record);
                $height = $this->getExteriorValue(11, $record);
                $breastDepth = $this->getExteriorValue(12, $record);
                $torsoLength = $this->getExteriorValue(13, $record);
                $inspectorFullName = $record[14];

                $animalId = ArrayUtil::get($vsmId, $this->animalIdsByVsmId);
                $inspectorId = $inspectorFullName !== '' ? $this->inspectorIdsInDbByFullName[$inspectorFullName] : 'NULL';

                $animalIdAndDate = $animalId.'_'.$measurementDateString;

                //The computationally easier validation checks are done first to speed up the process
                if ($animalId === null || $measurementDateString === null) {
                    $baseInsertBatchSet->incrementSkippedCount();
                    $insertBatchSet->incrementSkippedCount();
                    $this->sqlBatchProcessor->advanceProgressBar();
                    continue;
                }

                if (key_exists($animalIdAndDate, $exteriorMeasurementAnimalIdAndDates)
                 || key_exists($animalIdAndDate, $newExteriors)) {
                    $baseInsertBatchSet->incrementAlreadyDoneCount();
                    $insertBatchSet->incrementAlreadyDoneCount();
                    $this->sqlBatchProcessor->advanceProgressBar();
                    continue;
                }

                if (!$this->validateExteriorValues($record)) {
                    $baseInsertBatchSet->incrementSkippedCount();
                    $insertBatchSet->incrementSkippedCount();
                    $this->sqlBatchProcessor->advanceProgressBar();
                    continue;
                }

                //Insert new litter
                $baseInsertBatchSet->appendValuesString('('.++$id.",'".$logDate."',".$inspectorId.",'"
                    .$measurementDateString."','Exterior','".$animalIdAndDate."',".self::DEVELOPER_PRIMARY_KEY.")");

                $insertBatchSet->appendValuesString('('.$id.",".$animalId.",'"
                    .$skull."','"
                    .$muscularity."','"
                    .$proportion."','"
                    .$exteriorType."','"
                    .$legWork."','"
                    .$fur."','"
                    .$generalAppearance."','"
                    .$height."','"
                    .$breastDepth."','"
                    .$torsoLength."','"
                    .$kind."','"
                    .$progress."')");

                $newExteriors[$animalIdAndDate] = $animalIdAndDate;


                $this->sqlBatchProcessor
                    ->processAtBatchSize()
                    ->advanceProgressBar()
                ;
            }
            $this->sqlBatchProcessor->end();

        } catch (\Exception $exception) {
            $sql = "DELETE FROM measurement WHERE type = 'Exterior' AND DATE(log_date) = '$logDate'";
            $this->conn->exec($sql);

        } finally {
            $this->cmdUtil->writeln('First measurement Id inserted: '.$firstMaxId);
            $this->cmdUtil->writeln('Imported measurement logDate: '.$logDate);
        }


    }


    /**
     * @param int $key
     * @param array $record
     * @return float|int|null
     */
    private function getExteriorValue($key, $record)
    {
        $value = trim(strtr($record[$key], [',' => '.']));
        if ($value === '') {
            return 0;

        } elseif (is_numeric($value)) {
            return floatval($value);
        }

        return null;
    }


    /**
     * @param array $record
     * @return bool
     */
    private function validateExteriorValues(array $record)
    {
        $kind = $record[2];

        //0 OR Between 69 - 99
        $skull = $this->getExteriorValue(3, $record);
        $progress = $this->getExteriorValue(4, $record);
        $muscularity = $this->getExteriorValue(5, $record);
        $proportion = $this->getExteriorValue(6, $record);
        $exteriorType = $this->getExteriorValue(7, $record);
        $legWork = $this->getExteriorValue(8, $record);
        $fur = $this->getExteriorValue(9, $record);
        $generalAppearance = $this->getExteriorValue(10, $record);

        $valueTypes1 = [
            $skull, $progress, $muscularity, $proportion, $exteriorType, $legWork, $fur, $generalAppearance
        ];

        foreach ($valueTypes1 as $value) {
            if ($value === 0 || NumberUtil::isFloatZero($value)) { continue; }
            if ($value === null
             || $value < ExteriorValidator::DEFAULT_MIN_EXTERIOR_VALUE
             || $value > ExteriorValidator::DEFAULT_MAX_EXTERIOR_VALUE)
            {
                return false;
            }
        }

        //Between 0 - 99
        $height = $this->getExteriorValue(11, $record);
        $breastDepth = $this->getExteriorValue(12, $record);
        $torsoLength = $this->getExteriorValue(13, $record);

        $valueTypes2 = [
            $height, $breastDepth, $torsoLength
        ];

        foreach ($valueTypes2 as $value) {
            if ($value === 0 || NumberUtil::isFloatZero($value)) { continue; }
            if ($value === null
                || $value < 0
                || $value > ExteriorValidator::DEFAULT_MAX_EXTERIOR_VALUE)
            {
                return false;
            }
        }

        if (strlen($kind) !== 2) { return false; }

        return true;
    }

}