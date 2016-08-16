<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\Person;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoReadUbnbirthCommand extends ContainerAwareCommand
{
    const TITLE = 'Read UBN of birth from text file and save it to animal';
    const DEFAULT_INPUT_FOLDER_PATH = '/home/data/JVT/projects/NSFO/DocsVanNSFO/DierBedrijf.txt';
    const PERSIST_BATCH_SIZE = 250;
    const MAX_ROWS_TO_PROCESS = 0; //set 0 for no limit

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

        //Output folder input
        $inputFolder = $cmdUtil->generateQuestion('Please enter input folder path', self::DEFAULT_INPUT_FOLDER_PATH);

        $fileContents = file_get_contents($inputFolder);
        $dataInRows = explode("\r\n", $fileContents);


        $count = 0;
        foreach ($dataInRows as $row) {
            $row = str_replace(array('  '), ' ', $row); //remove double spaces
            $rowData = explode(" ", $row);

            $vsmAnimalPrimaryKey = $rowData[0];
            $dateOfBirth = $rowData[1];
            $ubnOfBirth = $rowData[2];
            if($ubnOfBirth == 'Onbekend') {
                $ubnOfBirth = null;
            }

            $this->getAnimalByVsmPrimaryKey($vsmAnimalPrimaryKey);

            $animal = $this->getAnimalByVsmPrimaryKey($vsmAnimalPrimaryKey);
            if($animal != null && $animal->getUbnOfBirth() == null) {
                $animal->setUbnOfBirth($ubnOfBirth);
                $em->persist($animal);
            }

            $count++;
            if($count%self::PERSIST_BATCH_SIZE) {
                $em->flush();
            }

            if($count == self::MAX_ROWS_TO_PROCESS) {
                break;
            }
        }
        $em->flush();


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


    public function getAnimalByVsmPrimaryKey($vsmPrimaryKey)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('name', $vsmPrimaryKey))
        ;

        /** @var Animal $animal */
        $animal = $this->em->getRepository(Animal::class)
            ->matching($criteria)->first();

        if(!$animal) {
            return null;
        } else {
            return $animal;
        }
    }
}
