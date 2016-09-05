<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use JMS\Serializer\Tests\Fixtures\ObjectWithIntListAndIntMap;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class NsfoMigratePedigreeregistersCommand extends ContainerAwareCommand
{
    const TITLE = 'Migrate PedigreeRegisters';
    const DEFAULT_OPTION = 0;
    const DEFAULT_START_ROW = 0;
    const ESTIMATED_REGISTER_COUNT = 10;

    /** @var ObjectManager $em */
    private $em;

    /** @var CommandUtil $em */
    private $cmdUtil;

    private $csv;

    /** @var int */
    private $startRow;

    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_out' => 'app/Resources/outputs/',
        'finder_name' => 'animal_stamboeken_van_inschrijving.csv',
        'ignoreFirstLine' => true
    );

    protected function configure()
    {
        $this
            ->setName('nsfo:migrate:pedigreeregisters')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ',"\n",
            'Generate PedigreeRegisters from source file (1)',"\n",
//            'Generate PedigreeRegisters from source file (2)',"\n",
            'Set PedigreeRegisters for all Animals (2)',"\n",
            'abort (other)',"\n"
        ], self::DEFAULT_OPTION);

        $this->startRow = $this->cmdUtil->generateQuestion('Please enter start row (default = '.self::DEFAULT_START_ROW.')', self::DEFAULT_START_ROW);
        $this->csv = $this->parseCSV();

        switch ($option) {
            case 1:
                $this->generatePedigreeRegisters();
                break;
            
            case 2:
                $this->setPedigreeRegistersForAllAnimals();
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }

        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    private function generatePedigreeRegisters()
    {
        $totalNumberOfRows = sizeof($this->csv);
        $this->cmdUtil->setStartTimeAndPrintIt($totalNumberOfRows + self::ESTIMATED_REGISTER_COUNT, $this->startRow);

        $pedigreeRegisters = new ArrayCollection();

        //Collect all pedigreeRegisters
        $count = 0;
        for($i = $this->startRow; $i < $totalNumberOfRows; $i++) {
            $row = $this->csv[$i];

            $rowData = explode(' : ',$row[1]);

            if(count($rowData) > 1) {
                $abbreviation = $rowData[0];
                $fullName = $rowData[1];
                $pedigreeRegisters->set($abbreviation, $fullName);
            }

            $this->cmdUtil->advanceProgressBar(1, 'Reading line: ' . $i . ' of ' . $totalNumberOfRows . '  | Lines processed: ' . $count);
        }

        $registerCount = 0;
        $abbreviations = $pedigreeRegisters->getKeys();
        $totalRegisters = $pedigreeRegisters->count();
        foreach ($abbreviations as $abbreviation) {
            $fullName = $pedigreeRegisters->get($abbreviation);

            $pedigreeRegister = new PedigreeRegister($abbreviation, $fullName);
            $this->em->persist($pedigreeRegister);

            $registerCount++;
            $this->cmdUtil->advanceProgressBar(1, 'Persisting new register: '.$abbreviation.' - '.$fullName.' | '.$registerCount.' of '.$totalRegisters);
        }
        $this->em->flush();
        $this->em->clear();
    }


    public function setPedigreeRegistersForAllAnimals()
    {
        /** @var AnimalRepository $animalRepository */
        $animalRepository = $this->em->getRepository(Animal::class);
        $animalPrimaryKeysByVsmId = $animalRepository->getAnimalPrimaryKeysByVsmId();

        $totalNumberOfRows = sizeof($this->csv);
        $this->cmdUtil->setStartTimeAndPrintIt($totalNumberOfRows, $this->startRow);

        $pedigreeRegisterPrimaryKeys = $this->getPedigreeRegisterPrimaryKeysByAbbreviation();

        $count = 0;
        for($i = $this->startRow; $i < $totalNumberOfRows; $i++) {
            $row = $this->csv[$i];

            $vsmId = $row[0];
            $rowData = explode(' : ',$row[1]);

            if(count($rowData) > 1) {
                $abbreviation = $rowData[0];
                $pedigreeRegisterId = $pedigreeRegisterPrimaryKeys->get($abbreviation);
                $animalId = $animalPrimaryKeysByVsmId->get($vsmId);

                if($animalId != null) {
                    $sql = "UPDATE animal SET pedigree_register_id = '". $pedigreeRegisterId ."' WHERE id = '". $animalId ."'";
                    $this->em->getConnection()->exec($sql);

                    $count++;
                }
            }

            $this->cmdUtil->advanceProgressBar(1, 'Reading line: ' . $i . ' of ' . $totalNumberOfRows . '  | Animals processed: ' . $count);
        }
    }


    /**
     * @return ArrayCollection
     */
    private function getPedigreeRegisterPrimaryKeysByAbbreviation()
    {
        $sql = "SELECT id, abbreviation FROM pedigree_register";
        $results = $this->em->getConnection()->query($sql)->fetchAll();

        $array = new ArrayCollection();
        foreach ($results as $result) {
            $array->set($result['abbreviation'], $result['id']);
        }

        return $array;
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
            while (($data = fgetcsv($handle, null, ",")) !== FALSE) {
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
