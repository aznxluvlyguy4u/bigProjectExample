<?php

namespace AppBundle\Command;

use AppBundle\Entity\Employee;
use AppBundle\Entity\Race;
use AppBundle\Enumerator\Specie;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class NsfoMigrateVsm2016novCommand extends ContainerAwareCommand
{
    const TITLE = 'Migrate vsm import files until 2016 nov';
    const DEFAULT_OPTION = 0;

    const RACES = 'races';
    const BIRTH = 'birth';
    const ANIMAL_RESIDENCE = 'animal_residence';
    const PERFORMANCE_MEASUREMENTS = 'performance_measurements';
    const ANIMAL_TABLE = 'animal_table';
    const BLINDNESS_FACTOR = 'blindness_factor';
    const MYO_MAX = 'myo_max';
    const TAG_REPLACES = 'tag_replaces';
    const PREDICATES = 'predicates';
    const SUBSCRIPTIONS = 'subscriptions';

    /** @var array */
    private $filenames;

    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/vsm2016nov',
        'finder_out' => 'app/Resources/outputs/migration',
        //'finder_name' => 'filename.csv',
        'ignoreFirstLine' => true
    );

    /** @var ObjectManager $em */
    private $em;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var OutputInterface */
    private $output;

    /** @var Employee */
    private $developer;

    protected function configure()
    {
        $this
            ->setName('nsfo:migrate:vsm2016nov')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $this->output = $output;
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);

        $this->developer = $em->getRepository(Employee::class)->find(2151);

        $this->filenames = array(
            self::RACES => 'rassen.txt',
            self::BIRTH => '20161007_1156_Diergeboortetabel.csv',
            self::ANIMAL_RESIDENCE => '20161007_1156_Diermutatietabel.csv',
            self::PERFORMANCE_MEASUREMENTS => '20161007_1156_Dierprestatietabel.csv',
            self::ANIMAL_TABLE => '20161007_1156_Diertabel.csv',
            self::BLINDNESS_FACTOR => '20161018_1058_DierBlindfactor.csv',
            self::MYO_MAX => '20161018_1058_DierMyoMax.csv',
            self::TAG_REPLACES => '20161018_1058_DierOmnummeringen.csv',
            self::PREDICATES => '20161019_0854_DierPredikaat_NSFO-correct.csv',
            self::SUBSCRIPTIONS => 'lidmaatschappen_voor_2010.txt',
        );

        //Setup folders if missing
        $rootDir = $this->getContainer()->get('kernel')->getRootDir();
        NullChecker::createFolderPathsFromArrayIfNull($rootDir, $this->csvParsingOptions);
        
        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: option 1', "\n",
            '2: Migrate Races', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1:
                $output->writeln('DONE!');
                break;

            case 2:
                $result = $this->migrateRaces() ? 'DONE' : 'NO DATA!' ;
                $output->writeln($result);
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }
    }


    private function parseCSV($filename) {
        $ignoreFirstLine = $this->csvParsingOptions['ignoreFirstLine'];

        $finder = new Finder();
        $finder->files()
            ->in($this->csvParsingOptions['finder_in'])
            ->name($filename)
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


    /**
     * @return bool
     */
    public function migrateRaces()
    {
        $data = $this->parseCSV($this->filenames[self::RACES]);

        if(count($data) == 0) { return false; }
        else { $this->cmdUtil->setStartTimeAndPrintIt(count($data)+1, 1); }

        $sql = "SELECT full_name FROM race";
        $results = $this->em->getConnection()->query($sql)->fetchAll();
        $searchArray = [];
        foreach ($results as $result) {
            $fullName = $result['full_name'];
            $searchArray[$fullName] = $fullName;
        }

        $newCount = 0;
        foreach ($data as $record) {

            $fullName = utf8_encode($record['2']);

            if(!array_key_exists($fullName, $searchArray)) {
                //$vsmId = $record['0'];
                $abbreviation = $record['1'];
                $startDate = new \DateTime($record['3']);
                $endDate = new \DateTime($record['4']);
                //$createdByString = $record['5'];
                //$creationDate = $record['6'];
                //$lastUpdatedByString = $record['7'];
                //$lastUpdateDate = $record['8'];
                $specie = $this->convertSpecieData($record['9']);

                $race = new Race($abbreviation, $fullName);
                $race->setStartDate($startDate);
                $race->setEndDate($endDate);
                $race->setCreatedBy($this->developer);
                $race->setCreationDate(new \DateTime());
                $race->setSpecie($specie);

                $this->em->persist($race);
                $newCount++;
            }
            $this->cmdUtil->advanceProgressBar(1);
        }
        $this->em->flush();
        $this->cmdUtil->setProgressBarMessage($newCount.' new records persisted');
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();

        return true;
    }

    /**
     * @param string $vsmSpecieData
     * @return string
     */
    private function convertSpecieData($vsmSpecieData){
        return strtr($vsmSpecieData, [ 'SC' => Specie::SHEEP, 'GE' => Specie::GOAT]);
    }
}
