<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\Person;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoReadUbnbirthCommand extends ContainerAwareCommand
{
    const TITLE = 'Read UBN of birth from text file and save it to animal';
    const DEFAULT_INPUT_FOLDER_PATH = '/home/data/JVT/projects/NSFO/DocsVanNSFO/DierBedrijf.txt';
    const PERSIST_BATCH_SIZE = 1000;
    const MAX_ROWS_TO_PROCESS = 0; //set 0 for no limit
    const DEFAULT_START_ROW = 0;

    /** @var EntityManager $em */
    private $em;

    protected function configure()
    {
        $this
            ->setName('nsfo:read:ubnbirth')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        //Timestamp
        $startTime = new \DateTime();
        $output->writeln(['Start time: '.date_format($startTime, 'Y-m-d h:m:s'),'']);

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->em = $em;

        //input
        $inputFile = $cmdUtil->generateQuestion('Please enter input file path', self::DEFAULT_INPUT_FOLDER_PATH);
        $startRow = $cmdUtil->generateQuestion('Choose start row (0 = default)', self::DEFAULT_START_ROW);

        $fileContents = file_get_contents($inputFile);
        $dataInRows = explode("\r\n", $fileContents);

        $this->readFileFindAnimalInDbAndPersistUbnOfBirth($startRow, $dataInRows, $output);

        //Final Results
        $endTime = new \DateTime();
        $elapsedTime = gmdate("H:i:s", $endTime->getTimestamp() - $startTime->getTimestamp());

        $output->writeln([
            '=== PROCESS FINISHED ===',
            'End Time: '.date_format($endTime, 'Y-m-d h:m:s'),
            'Elapsed Time (h:m:s): '.$elapsedTime,
            '',
            '']);
    }


    /**
     * @param int $startRow
     * @param array $dataInRows
     * @param OutputInterface $output
     */
    private function readFileFindAnimalInDbAndPersistUbnOfBirth($startRow, $dataInRows, OutputInterface $output)
    {
        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $maxRowCount = sizeof($dataInRows);

        for($i = $startRow; $i < $maxRowCount; $i++) {
            $row = $dataInRows[$i];
//            $row = str_replace(array('  '), ' ', $row); //remove double spaces
            $rowData = explode(" ", $row);

            if(sizeof($rowData) >= 3) {

                $vsmAnimalPrimaryKey = $rowData[0];
                $dateOfBirth = $rowData[1];
                $ubnOfBirth = $rowData[2];
                if ($ubnOfBirth == 'Onbekend') {
                    $ubnOfBirth = null;
                }

                /** @var Animal $animal */
                $animal = $this->em->getRepository(Animal::class)->findOneByName($vsmAnimalPrimaryKey);
                if($animal != null && $animal->getUbnOfBirth() == null) {
                    $animal->setUbnOfBirth($ubnOfBirth);
                    $em->persist($animal);
                }

                if ($i%self::PERSIST_BATCH_SIZE == 0) {
                    $em->flush();
                    $em->clear();
                    gc_collect_cycles();
                    $output->writeln('Now at row: '.$i.' of '.$maxRowCount);
                }

                if ($i-$startRow+1 == self::MAX_ROWS_TO_PROCESS) {
                    break;
                }
            }
        }
        $em->flush();
        $em->clear();
        gc_collect_cycles();
    }
}
