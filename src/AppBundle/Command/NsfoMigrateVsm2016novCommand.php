<?php

namespace AppBundle\Command;

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
    
    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_out' => 'app/Resources/outputs/migration',
        'finder_name' => 'filename.csv',
        'ignoreFirstLine' => true
    );

    /** @var ObjectManager $em */
    private $em;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var OutputInterface */
    private $output;


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

        //Setup folders if missing
        $rootDir = $this->getContainer()->get('kernel')->getRootDir();
        NullChecker::createFolderPathsFromArrayIfNull($rootDir, $this->csvParsingOptions);
        
        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: option 1', "\n",
            '2: option 2', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1:
                $output->writeln('DONE!');
                break;

            case 2:
                $output->writeln('DONE!');
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }
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
}
