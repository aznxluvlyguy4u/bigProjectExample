<?php


namespace AppBundle\Service\Migration;


use AppBundle\Cache\WeightCacher;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\MeasurementsUtil;
use Doctrine\Common\Persistence\ObjectManager;

class ScanMeasurementsMigrator extends Migrator2017JunServiceBase implements IMigratorService
{
    const IMPORT_SUB_FOLDER = 'measurements';
    const FILENAME = 'scan_measurements_2017.csv';


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

        $this->writeLn('====== PRE migration fixes ======');
        $this->data = $this->parseCSV(self::FILENAME);

        $this->createInspectorSearchArrayAndInsertNewInspectors(9);
        MeasurementsUtil::generateAnimalIdAndDateValues($this->conn, false, $cmdUtil);

        $this->writeln('====== Validate data ======');
        $this->validateData();

        $this->writeln('====== Migrate scan measurements ======');
        $this->migrateNewScanMeasurements();

        $this->writeLn('====== POST migration updates ======');
        $this->cmdUtil->writeln( WeightCacher::updateAllWeights($this->conn) . ' weight cache records updated');
    }


    private function validateData()
    {
        foreach ($this->data as $record) {
            $ulnCountryCode = $record[0];
            $ulnNumber = $record[1];
            $ubn = $record[2];
            $measurementDateString = $record[3];
            $scanWeight = $record[4];
            $fat1 = $record[5];
            $fat2 = $record[6];
            $fat3 = $record[7];
            $muscleThickness = $record[8];
            $inspectorFullName = $record[9];

            // TODO;
        }
    }


    private function migrateNewScanMeasurements()
    {
        foreach ($this->data as $record) {
            $ulnCountryCode = $record[0];
            $ulnNumber = $record[1];
            $ubn = $record[2];
            $measurementDateString = $record[3];
            $scanWeight = $record[4];
            $fat1 = $record[5];
            $fat2 = $record[6];
            $fat3 = $record[7];
            $muscleThickness = $record[8];
            $inspectorFullName = $record[9];
        }
    }
}