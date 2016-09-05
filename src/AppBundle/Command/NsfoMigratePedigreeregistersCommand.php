<?php

namespace AppBundle\Command;

use AppBundle\Util\CommandUtil;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class NsfoMigratePedigeeregistersCommand extends ContainerAwareCommand
{
    const TITLE = 'TESTING';
    const DEFAULT_START_ROW = 0;

    /** @var ObjectManager $em */
    private $em;

    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_out' => 'app/Resources/outputs/',
        'finder_name' => 'filename.csv',
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
        $cmdUtil = new CommandUtil($input, $output, $helper);

//        $outputFolder = $this->getContainer()->get('kernel')->getRootDir().'/Resources/outputs';
//        NullChecker::createFolderPathIfNull($outputFolder);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $startMessage = 'SETUP';

        $isOnlyCreatePedigreeRegisters = $cmdUtil->generateConfirmationQuestion('Only generate pedigreeRegisters from source file? (y/n)');
        $startRow = $cmdUtil->generateQuestion('Please enter start row (default = '.self::DEFAULT_START_ROW.'): ', self::DEFAULT_START_ROW);

        $csv = $this->parseCSV();
        $totalNumberOfRows = sizeof($csv);

        $cmdUtil->setStartTimeAndPrintIt($totalNumberOfRows, $startRow, $startMessage);

        $pedigreeRegisters = new ArrayCollection();

        $count = 0;
        for($i = $startRow; $i < 10; $i++) {
            $row = $csv[$i];

            dump($row);die;
            $pedigreeRegisters->set('d', 'd');

            $cmdUtil->advanceProgressBar(1, 'Reading line: ' . $i . ' of ' . $totalNumberOfRows . '  | Lines processed: ' . $count);
        }

        $cmdUtil->setEndTimeAndPrintFinalOverview();
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
            while (($data = fgetcsv($handle, 30, ";")) !== FALSE) {
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
