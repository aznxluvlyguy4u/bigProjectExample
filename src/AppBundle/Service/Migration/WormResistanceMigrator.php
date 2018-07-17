<?php


namespace AppBundle\Service\Migration;

use AppBundle\Enumerator\QueryType;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class WormResistanceMigrator
 */
class WormResistanceMigrator extends Migrator2017JunServiceBase implements IMigratorService
{
    const MAX_EPG = 30000;
    const HAS_AFTER_2017_FORMAT = true;

    /** @var array */
    private $animalIdsByUln;
    /** @var array */
    private $animalIdsByUniqueStn;


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

        $this->writeLn('Get animalIds by uln search array ...');
        $this->animalIdsByUln = $this->animalRepository->getAnimalPrimaryKeysByUlnStringArrayIncludingTagReplaces();
        if (self::HAS_AFTER_2017_FORMAT) {
            $this->animalIdsByVsmId = [];
            $this->animalIdsByUniqueStn = [];
        } else {
            $this->writeLn('Get animalIds by vsmId search array ...');
            $this->animalIdsByVsmId = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();
            $this->writeLn('Get animalIds by unique stn search array ...');
            $this->animalIdsByUniqueStn = $this->animalRepository->getAnimalPrimaryKeysByUniqueStnArray();
        }

        $this->writeLn('Get current WormResistance records search array ...');
        if (self::HAS_AFTER_2017_FORMAT) {
            $sql = "SELECT CONCAT(animal_id,'|',year) as key FROM worm_resistance";
        } else {
            $sql = "SELECT CONCAT(animal_id,'|',year,sample_period) as key FROM worm_resistance";
        }
        $currentRecords = SqlUtil::getSingleValueGroupedSqlResults(
            'key', $this->conn->query($sql)->fetchAll(), false, true);

        $insertBatchSet = $this->sqlBatchProcessor
            ->purgeAllSets()
            ->createBatchSet(QueryType::INSERT)
            ->getSet(QueryType::INSERT)
        ;

        $insertBatchSet->setSqlQueryBase("INSERT INTO worm_resistance (animal_id, log_date, sampling_date, year,
                 treated_for_samples, epg, s_iga_glasgow, carla_iga_nz, class_carla_iga_nz, sample_period, treatment_ubn) VALUES ");

        $this->data = $this->parseCSV(self::WORM_RESISTANCE);
        $this->sqlBatchProcessor->start(count($this->data));

        $missingUlns = [];

        foreach ($this->data as $record) {

            /*
             * 2014 - 2017 Format
             *
            //$ubnDashAnimalOrderNumber = $record[0];
            //$vsmId = $record[1]; //string/int
            //$stnOrUln = $record[2]; //string
            //$uln = $record[3]; //string
            //$ulnCountryCode = $record[4]; //string
            //$ulnNumber = $record[5]; //string
            $sampleDateString = $this->parseDateString($record[6]); //Date YYYY-MM-DD
            $year = $this->parseYear($record[7]); //int
            //$companyName = $record[8]; //int
            $treatmentUbn = $this->parseUbn($record[9]); //int
            //$animalOrderNumber = $record[10]; //int
            $treatedForSamples = $this->getTreatedForSamples($record[11]); //nee/ja/null/''
            $epg = $this->getEpg($record[12]); //int MAX 30000
            $sIgaGlasgow = $this->parseFloat($record[13]); //float
            $carlaIgaNz = $this->parseFloat($record[14]); //float
            $classCarlaIgaNz = SqlUtil::getNullCheckedValueForSqlQuery($record[15], true); //string
            $samplePeriod = SqlUtil::getNullCheckedValueForSqlQuery($record[16], false); //int
             *
             *
             */


            /*
             * 2018+ format
             */
            $treatmentUbn = $this->parseUbn($record[0]); //int
            $treatedForSamples = $this->getTreatedForSamples($record[1]); // Ontwormd nee/ja/null/''
            $sampleDateString = $this->parseDateString($record[2]); //Date YYYY-MM-DD
            $sIgaGlasgow = $this->parseFloat($record[5]); //float
            $year = $this->parseYearFromDateString($record[2]); //int
            $samplePeriod = SqlUtil::NULL;
            $epg = SqlUtil::NULL;
            $carlaIgaNz = SqlUtil::NULL;
            $classCarlaIgaNz = SqlUtil::NULL;
            /*
             *
             */

            $animalId = $this->getAnimalId($record, self::HAS_AFTER_2017_FORMAT);

            $uniqueSearchKey = self::HAS_AFTER_2017_FORMAT ? $animalId.'|'.$year : $animalId.'|'.$year.$record[16];

            if (self::HAS_AFTER_2017_FORMAT && $animalId === null) {
                $missingUlns[] = $record[3].$record[4];
            }

            if ($animalId === null || key_exists($uniqueSearchKey, $currentRecords)) {
                $insertBatchSet->incrementSkippedCount();
                continue;
            }

            $insertBatchSet->appendValuesString("(".$animalId . ",NOW()," . $sampleDateString . "," . $year . ","
                . $treatedForSamples . "," . $epg . "," . $sIgaGlasgow . ","
                . $carlaIgaNz . "," . $classCarlaIgaNz . "," . $samplePeriod . "," . $treatmentUbn . ")");


            $this->sqlBatchProcessor
                ->processAtBatchSize()
                ->advanceProgressBar()
            ;
        }
        $this->sqlBatchProcessor
            ->end()
            ->purgeAllSets();

        $this->fillEmptyTreatmentUbnsWithCurrentUbns();

        if (!empty($missingUlns)) {
            $this->writeLn(count($missingUlns).' Missing ULNs');
            $prefix = '';
            $searchString = 'SELECT * FROM animal WHERE CONCAT(uln_country_code, uln_number) IN (';
            foreach ($missingUlns as $missingUln) {
                $searchString .= $prefix . "'" . $missingUln . "'";
                $prefix = ',';
            }
            $this->writeLn($searchString.')');
        }
    }


    private function fillEmptyTreatmentUbnsWithCurrentUbns()
    {
        try {
            $sql = "UPDATE worm_resistance SET treatment_ubn = v.new_treatment_ubn
                FROM (
                  SELECT
                    w.id,
                --     w.animal_id,
                --     w.treatment_ubn,
                --     a.ubn_of_birth,
                --     l.ubn as current_ubn,
                    COALESCE(w.treatment_ubn, CAST(a.ubn_of_birth AS INTEGER), CAST(l.ubn AS INTEGER)) as new_treatment_ubn
                  FROM worm_resistance w
                    INNER JOIN animal a ON w.animal_id = a.id
                    LEFT JOIN location l ON a.location_id = l.id
                  WHERE w.treatment_ubn ISNULL AND (a.ubn_of_birth NOTNULL OR l.ubn NOTNULL)
                ) AS v(w_id, new_treatment_ubn) WHERE worm_resistance.id = v.w_id";
            $updateCount = SqlUtil::updateWithCount($this->conn, $sql);
            $updateCountText = $updateCount === 0 ? 'No' : $updateCount;
            $this->writeLn($updateCountText . ' empty treatment ubns where filled using ubnOfBirths or currentUbns');

        } catch (\Exception $exception) {
            $this->writeLn('An error occured trying to fill the empty TreatmentUbns.');
            $this->writeLn($exception->getMessage());
            $this->writeLn($exception->getTraceAsString());
        }
    }


    /**
     * @param string $value
     * @return bool|null
     */
    private function getTreatedForSamples($value)
    {
        if ($value === 'nee' || $value === 'FALSE' || $value === false) {
            return 'FALSE';
        } elseif ($value === 'ja' || $value === 'TRUE' || $value === true) {
            return 'TRUE';
        } else {
            return SqlUtil::NULL;
        }
    }


    /**
     * @param $epg
     * @return string
     */
    private function getEpg($epg)
    {
        if (ctype_digit($epg) || is_int($epg)) {
            if (intval($epg) <= self::MAX_EPG) {
                return SqlUtil::getNullCheckedValueForSqlQuery($epg, true); //int MAX 30000
            }
        }
        return SqlUtil::NULL;
    }

    /**
     * @param $float
     * @return mixed
     */
    private function parseFloat($float)
    {
        if ($float === '' || $float === '#VALUE!') {
            return SqlUtil::NULL;
        }
        $float = str_replace(',','.', $float);
        return SqlUtil::getNullCheckedValueForSqlQuery($float, true);
    }


    /**
     * @param $ubn
     * @return string
     */
    private function parseUbn($ubn)
    {
        if (ctype_digit($ubn) || is_int($ubn)) {
            return SqlUtil::getNullCheckedValueForSqlQuery($ubn, false);
        }
        return SqlUtil::NULL;
    }


    /*
     * Priority of checks for animalId
     * 1. vsmId
     * 2. $uln for uln
     * 3. $stnOrUln for uln
     * 4. $stnOrUln for stn
     */
    private function getAnimalId($record, $isAfter2017Format = true)
    {
        if ($isAfter2017Format) {
            $ulnCountryCode = $record[3]; //string
            $ulnNumber = $record[4]; //string
            $vsmId = '';
            $stnOrUln = '';
            $uln = '';
        } else {
            $vsmId = $record[1]; //string/int
            $stnOrUln = $record[2]; //string
            $uln = $record[3]; //string
            $ulnCountryCode = $record[4]; //string
            $ulnNumber = $record[5]; //string
        }

        $separateUlnValuesExists = $ulnCountryCode != '' && $ulnNumber != '';
        $unifiedUlnValueExist = $uln != '';

        $animalId = null;
        if ($separateUlnValuesExists || $unifiedUlnValueExist) {
            if ($separateUlnValuesExists) {
                $uln = strtoupper($ulnCountryCode).$ulnNumber;
            }
            //Remove any spaces
            $uln = str_replace(' ','',$uln);

            if (Validator::verifyUlnFormat($uln)) {
                $animalId = ArrayUtil::get($uln, $this->animalIdsByUln);
            } else {
                /*
                    Find Luxemburg animals by ulnCountryCode = 'LU' and animalOrderNumber in the ulnNumber column
                    The UBN value in the csv file cannot be used at the moment,
                    because the Luxemburg UBN location_ids are not set to animals yet.
                */
                $animalId = $this->getLuxemburgAnimalId($ulnCountryCode, $ulnNumber);
            }

        } elseif ($vsmId != '') {
            $animalId = ArrayUtil::get($vsmId, $this->animalIdsByVsmId);

        } elseif ($stnOrUln != '') {
            //Remove any spaces
            $stnOrUln = str_replace(' ','',$stnOrUln);
            if (Validator::verifyUlnFormat($stnOrUln)) {
                $animalId = ArrayUtil::get($uln, $this->animalIdsByUln);

            } elseif (Validator::verifyPedigreeCountryCodeAndNumberFormat($stnOrUln)) {
                $animalId = ArrayUtil::get($stnOrUln, $this->animalIdsByUniqueStn);
            }

        } else {
            $animalId = null;
        }

        return $animalId;
    }


    /**
     * @param string $ulnCountryCode
     * @param string|int $animalOrderNumber
     * @return int|null
     */
    private function getLuxemburgAnimalId($ulnCountryCode, $animalOrderNumber)
    {
        if (!strtoupper(trim($ulnCountryCode)) !== 'LU' || !Validator::verifyAnimalOrderNumberFormat($animalOrderNumber)) {
            return null;
        }

        $sql = "SELECT id FROM animal WHERE animal_order_number = '$animalOrderNumber' AND uln_country_code = 'LU'";
        $results = $this->conn->query($sql)->fetchAll();

        if (count($results) !== 1) {
            return intval($results[0]['id']);
        }

        //Ignore ambiguous results and empty results
        return null;
    }

}