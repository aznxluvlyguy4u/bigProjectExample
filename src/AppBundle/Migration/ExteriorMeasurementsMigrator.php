<?php


namespace AppBundle\Migration;


use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\AnimalResidenceRepository;
use AppBundle\Entity\Inspector;
use AppBundle\Entity\InspectorRepository;
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
        dump($inspectorIdsInDbByFullName);die; //TODO
        $this->animalIdsByVsmIds = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();
        
        
        $totalNumberOfRows = sizeof($this->data);
        $this->output->writeln('=== Migrating exteriorMeasurements ===');
        $this->cmdUtil->setStartTimeAndPrintIt($totalNumberOfRows, $startCounter);

        $inspectors = [];

        $counter = 0;
        for($i = $startCounter; $i < $totalNumberOfRows; $i++) {

            $line = $this->data[$i];

            //Rows above 14 are empty

            $vsmId = $line[0];
            $measurementDate = TimeUtil::fillDateStringWithLeadingZeroes($line[1]);
            $kind = $line[2];
            $skull = $line[3];
            $progress = $line[4];
            $muscularity = $line[5];
            $proportion = $line[6];
            $exteriorType = $line[7];
            $legWork = $line[8];
            $fur = $line[9];
            $generalAppearance = $line[10];
            $height = $line[11];
            $breastDepth = $line[12];
            $torsoLength = $line[13];
            $inspectorName = $line[14];

            $inspectors[$inspectorName] = $inspectorName;

//            if($line[1] != '' && $line[1] != null) {
//
//                $name = $line[0];
//                $measurementDate = new \DateTime(StringUtil::changeDateFormatStringFromAmericanToISO($line[1]));
//                $measurementDateStamp = $measurementDate->format('Y-m-d H:i:s');
//                $measurementDate->add(new \DateInterval('P1D'));
//                $nextDayStamp = $measurementDate->format('Y-m-d H:i:s');
//
//                $kind = $line[2];
//                $progress = (float) $line[3];
//                $height = (float) $line[4];
//
//                $message = $i; //defaultMessage
//
//                //TODO
//
//                $this->cmdUtil->advanceProgressBar(1, $message);
//            }

        }

        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
        $this->output->writeln("LINES IMPORTED: " . $counter);
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
    
}