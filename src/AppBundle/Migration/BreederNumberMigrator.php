<?php


namespace AppBundle\Migration;


use AppBundle\Entity\BreederNumber;
use AppBundle\Entity\BreederNumberRepository;
use AppBundle\Enumerator\BreederNumberSourceType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class BreederNumberMigrator extends MigratorBase
{
    /** @var BreederNumberRepository */
    private $breederNumberRepository;
    
    /**
     * BreederNumberMigrator constructor.
     * @param CommandUtil $cmdUtil
     * @param ObjectManager $em
     * @param OutputInterface $outputInterface
     * @param array $data
     * @param string $rootDir
     */
    public function __construct(CommandUtil $cmdUtil, ObjectManager $em, OutputInterface $outputInterface, array $data, $rootDir)
    {
        parent::__construct($cmdUtil, $em, $outputInterface, $data, $rootDir);
        $this->breederNumberRepository = $this->em->getRepository(BreederNumber::class);
    }
    
    public function migrate()
    {
        $searchArray = $this->breederNumberRepository->getCurrentRecordsSearchArray();
        $breederNumbersFromMigrationTable = $this->getUbnsOfBirthByBreederNumberFromAnimalMigrationTable();

        $this->cmdUtil->setStartTimeAndPrintIt(count($this->data)+count($breederNumbersFromMigrationTable)+1, 1);

        $newCount = 0;
        $skipped = 0;
        foreach ($this->data as $record) {

            $breederNumber = StringUtil::padBreederNumberWithZeroes($record[1]);
            $ubnOfBirth = $record[8];
            //Skip iteration, if ubnOfBirth has an invalid format
            if(!Validator::hasValidUbnFormat($ubnOfBirth) || NullChecker::isNull($record[1])) {
                $this->cmdUtil->advanceProgressBar(1, 'New breederNumber records: '.$newCount.' | csv records skipped: '.$skipped);
                continue;
            }

            $persistNewRecord = false;
            if(!array_key_exists($breederNumber, $searchArray)) {
                $persistNewRecord = true;
            //Migrating data from CSV takes precedence over data generated from AnimalMigrationTable
            } elseif ($searchArray[$breederNumber] == BreederNumberSourceType::ANIMAL_MIGRATION_TABLE) {
                $persistNewRecord = true;
            }

            if($persistNewRecord) {
                $this->breederNumberRepository->insertNewRecordBySql($breederNumber, $ubnOfBirth, BreederNumberSourceType::CSV_IMPORT);
                $searchArray[$breederNumber] = BreederNumberSourceType::CSV_IMPORT;
                $newCount++;
            } else {
                $skipped++;
            }

            $this->cmdUtil->advanceProgressBar(1, 'New breederNumber csv records: '.$newCount.' | csv records skipped: '.$skipped);
        }

        $newCountFromMigrationTable = 0;
        $breederNumbers = array_keys($breederNumbersFromMigrationTable);
        foreach ($breederNumbers as $breederNumber) {
            if(!array_key_exists($breederNumber, $searchArray)) {
                $ubnOfBirth = $breederNumbersFromMigrationTable[$breederNumber];                $this->breederNumberRepository->insertNewRecordBySql($breederNumber, $ubnOfBirth, BreederNumberSourceType::ANIMAL_MIGRATION_TABLE);
                $searchArray[$breederNumber] = BreederNumberSourceType::ANIMAL_MIGRATION_TABLE;
                $newCountFromMigrationTable++;
            }
            $this->cmdUtil->advanceProgressBar(1, 'New breederNumber csv records: '.$newCount.' | csv records skipped: '.$skipped.' | new breederNumber migrationTable records: '.$newCountFromMigrationTable);
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getUbnsOfBirthByBreederNumberFromAnimalMigrationTable()
    {
        $sql = "SELECT pedigree_number, stn_origin, ubn_of_birth FROM animal_migration_table t
                WHERE pedigree_country_code = 'NL' AND t.ubn_of_birth NOTNULL AND t.stn_origin NOTNULL ";
        $results = $this->conn->query($sql)->fetchAll();

        $searchArray = [];
        foreach ($results as $result) {
            $pedigreeNumber = $result['pedigree_number'];
            if($pedigreeNumber != null && $pedigreeNumber != '') {
                $breederNumber = StringUtil::getBreederNumberFromPedigreeNumber($pedigreeNumber);
            } else {
                $stnOrigin = $result['stn_origin'];
                $breederNumber = StringUtil::getBreederNumberFromStnOrigin($stnOrigin);
            }
            $ubnOfBirth = $result['ubn_of_birth'];

            if(Validator::hasValidUbnFormat($ubnOfBirth)) {
                $searchArray[$breederNumber] = $ubnOfBirth;
            }
        }
        return $searchArray;
    }

    
}