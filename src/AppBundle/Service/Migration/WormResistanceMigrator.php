<?php


namespace AppBundle\Service\Migration;

use AppBundle\Enumerator\QueryType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\SqlUtil;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class WormResistanceMigrator
 */
class WormResistanceMigrator extends Migrator2017JunServiceBase implements IMigratorService
{

    /** @inheritdoc */
    public function __construct(ObjectManager $em, $rootDir)
    {
        parent::__construct($em, $rootDir);
    }


    /**
     * @inheritDoc
     */
    function run(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        DoctrineUtil::updateTableSequence($this->conn, ['worm_resistance']);

        $sql = "SELECT CONCAT(animal_id,'|',year) as key FROM worm_resistance";
        $currentRecords = SqlUtil::getSingleValueGroupedSqlResults(
            'key', $this->conn->query($sql)->fetchAll(), false, true);

        //TODO animalIdByUln and animalIdByStn
        $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();

        $insertBatchSet = $this->sqlBatchProcessor
            ->purgeAllSets()
            ->createBatchSet(QueryType::INSERT)
            ->getSet(QueryType::INSERT)
        ;

        $insertBatchSet->setSqlQueryBase("INSERT INTO worm_resistance (animal_id, log_date, year, treated_for_samples, epg, s_iga_glasgow, carla_iga_nz, sample_period) VALUES ");

        $this->data = $this->parseCSV(self::WORM_RESISTANCE);
        $this->sqlBatchProcessor->start(count($this->data));

        foreach ($this->data as $record) {
            //$vsmId = $record[0]; //string/int
            //$stnOrUln = $record[1]; //string
            //$uln = $record[2]; //string
            $year = $record[3]; //int
            $treatedForSamples = $this->getTreatedForSamples($record[4]); //nee/ja/null/''
            $epg = SqlUtil::getNullCheckedValueForSqlQuery($record[5], true); //float/int
            $sIgaGlasgow = SqlUtil::getNullCheckedValueForSqlQuery($record[6], true); //float
            $carlaIgaNz = SqlUtil::getNullCheckedValueForSqlQuery($record[7], true); //float
            $classCarlaIgaNz = SqlUtil::getNullCheckedValueForSqlQuery($record[8], true); //string
            $samplePeriod = SqlUtil::getNullCheckedValueForSqlQuery($record[9], false); //int

            $animalId = $this->getAnimalId($record);

            if (key_exists($animalId.'|'.$year, $currentRecords)) {
                $insertBatchSet->incrementSkippedCount();
                continue;
            }

            $insertBatchSet->appendValuesString("(".$animalId . ",NOW()," . $year . ","
                . $treatedForSamples . "," . $epg . "," . $sIgaGlasgow . ","
                . $carlaIgaNz . "," . $classCarlaIgaNz . "," . $samplePeriod . ")");
            $insertBatchSet->incrementBatchCount();


            $this->sqlBatchProcessor
                ->processAtBatchSize()
                ->advanceProgressBar()
            ;
        }
        $this->sqlBatchProcessor
            ->end()
            ->purgeAllSets();
    }


    /**
     * @param string $value
     * @param string $nullReplacement
     * @return bool|null
     */
    private function getTreatedForSamples($value, $nullReplacement = 'NULL')
    {
        if ($value === 'nee') {
            return false;
        } elseif ($value === 'ja') {
            return true;
        } else {
            return $nullReplacement;
        }
    }


    /*
     * Priority of checks for animalId
     * 1. vsmId
     * 2. $uln for uln
     * 3. $stnOrUln for uln
     * 4. $stnOrUln for stn
     */
    private function getAnimalId($record)
    {
        $vsmId = $record[0]; //string/int
        $stnOrUln = $record[1]; //string
        $uln = $record[2]; //string

        //TODO use animalIdBy vsmId/Uln/Stn

        return 0;
    }
}