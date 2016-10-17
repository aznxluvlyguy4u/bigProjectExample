<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\Person;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoReadUbnbirthCommand extends ContainerAwareCommand
{
    const TITLE = 'Read UBN of birth from text file and save it to animal';
    const DEFAULT_FILE_NAME = 'DierBedrijf.txt';
    const PERSIST_BATCH_SIZE = 1000;
    const MAX_ROWS_TO_PROCESS = 0; //set 0 for no limit
    const DEFAULT_START_ROW = 0;

    /** @var ObjectManager $em */
    private $em;

    /** @var string */
    private $defaultFolderPath;

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

        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;

        $this->defaultFolderPath = $this->getContainer()->get('kernel')->getRootDir().'/Resources/imports';

        //input
        $isProcessUbnsOfBirth = $cmdUtil->generateConfirmationQuestion('Process UBN of Birth? (y/n): ');
        $isProcessDateOfBirths = $cmdUtil->generateConfirmationQuestion('Process Date of Births? (y/n): ');
        $inputFile = $cmdUtil->generateQuestion('Please enter input file path', $this->defaultFolderPath.'/'.self::DEFAULT_FILE_NAME);

        $fileContents = file_get_contents($inputFile);
        $dataInRows = explode("\r\n", $fileContents);

        if($isProcessUbnsOfBirth) {
            $startRow = $cmdUtil->generateQuestion('UBN Processing: Choose start row (0 = default)', self::DEFAULT_START_ROW);
            $this->readFileFindAnimalInDbAndPersistUbnOfBirth($startRow, $dataInRows, $output);
        }

        if($isProcessDateOfBirths) {
            $this->readFileAndAddDateOfBirthForAnimalsWithoutOne($dataInRows, $output);
        }

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
                if ($ubnOfBirth == 'Onbekend' || $ubnOfBirth == 'Afgevoerd') {
                    $ubnOfBirth = null;
                }

                /** @var Animal $animal */
                $animal = $this->em->getRepository(Animal::class)->findOneByName($vsmAnimalPrimaryKey);
                if($animal != null && $animal->getUbnOfBirth() == null) {
                    $animal->setUbnOfBirth($ubnOfBirth);
                    $em->persist($animal);
                }

                if ($i%self::PERSIST_BATCH_SIZE == 0) {
                    $this->flushPlus();
                    $output->writeln('Now at row: '.$i.' of '.$maxRowCount);
                }

                if ($i-$startRow+1 == self::MAX_ROWS_TO_PROCESS) {
                    break;
                }
            }
        }
        $this->flushPlus();
    }

    /**
     * @param $dataInRows
     * @param OutputInterface $output
     */
    private function readFileAndAddDateOfBirthForAnimalsWithoutOne($dataInRows, OutputInterface $output)
    {
        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $vsmAnimalPrimaryKeyAndDateOfBirth = new ArrayCollection();
        $vsmAnimalPrimaryKeyAndPlaceholderDateOfBirth = new ArrayCollection();

        /* Parse the data from the text file into quickly searchable ArrayCollections */
        foreach ($dataInRows as $row) {
            $rowData = explode(" ", $row);

            //This is not a real date in the source file, just a placeholder for missing dates
            $placeHolderForMissingDateOfBirths = '19700101';

            if(sizeof($rowData) >= 3) {
                $vsmAnimalPrimaryKey = $rowData[0];
                $dateOfBirthString = $rowData[1];

                if($dateOfBirthString != $placeHolderForMissingDateOfBirths) {
                    $vsmAnimalPrimaryKeyAndDateOfBirth->set($vsmAnimalPrimaryKey,$dateOfBirthString);
                } else {
                    $vsmAnimalPrimaryKeyAndPlaceholderDateOfBirth->set($vsmAnimalPrimaryKey,$dateOfBirthString);
                }
            }
        }


        /* Remove fake DateOfBirths */
        $animalsWithFakeDateOfBirth = $this->getAnimalsWithFakeDateOfBirth();
        $animalsCount = $animalsWithFakeDateOfBirth->count();
        $count = 0;
        $fakeCount = 0;
        $missingCount = 0;
        /** @var Animal $animal */
        foreach ($animalsWithFakeDateOfBirth as $animal) {
            //Verify if dateOfBirth of animal is fake. And if fake delete it.
            $isDateOfBirthFake = $vsmAnimalPrimaryKeyAndPlaceholderDateOfBirth->get($animal->getName()) != null;
            if($isDateOfBirthFake) {
                $animal->setDateOfBirth(null);
                $fakeCount++;
            } else {
                //doNothing
                $missingCount++;
            }
            if(++$count%self::PERSIST_BATCH_SIZE == 0) {
                $this->flushPlus();
                $output->writeln('Animals processed: '.$count.' of '.$animalsCount.'. Fake DateOfBirths removed: '.$fakeCount.' Not Proven Fake: '.$missingCount);
            }
        }
        $this->flushPlus();
        $output->writeln('Animals processed: '.$count.' of '.$animalsCount.'. Fake DateOfBirths removed: '.$fakeCount.' Not Proven Fake: '.$missingCount);


        /* Persist new DateOfBirths */
        $animals = $this->getAnimalsWithoutDateOfBirth();
        $totalAnimals = $animals->count();
        $updateAnimalsCount = 0;
        $noDateOfBirthFoundCount = 0;
        $count = 0;

        /** @var Animal $animal */
        foreach ($animals as $animal) {
            $vsmAnimalPrimaryKey = $animal->getName();
            $dateOfBirthString = $vsmAnimalPrimaryKeyAndDateOfBirth->get($vsmAnimalPrimaryKey);
            if($dateOfBirthString != null) {
                $animal->setDateOfBirth(new \DateTime($dateOfBirthString));
                $em->persist($animal);
                $updateAnimalsCount++;
            } else {
                $noDateOfBirthFoundCount++;
            }

            if(++$count%self::PERSIST_BATCH_SIZE == 0) {
                $this->flushPlus();
                $output->writeln('Animals processed: '.$count.' of '.$totalAnimals.'. New DateOfBirth: '.$updateAnimalsCount.' DateOfBirth missing: '.$noDateOfBirthFoundCount);
            }
        }
        $this->flushPlus();
        $output->writeln('Animals processed: '.$count.' of '.$totalAnimals.'. New DateOfBirth: '.$updateAnimalsCount.' DateOfBirth missing: '.$noDateOfBirthFoundCount);
    }
    
    private function flushPlus()
    {
        $this->em->flush();
        $this->em->clear();
        gc_collect_cycles();
    }

    /**
     * @return Collection
     */
    public function getAnimalsWithoutDateOfBirth()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('dateOfBirth', null))
            ;

        $animals = $this->em->getRepository(Animal::class)->matching($criteria);
        return $animals;
    }

    /**
     * @return Collection
     */
    public function getAnimalsWithFakeDateOfBirth()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('dateOfBirth', new \DateTime('19700101')))
        ;

        $animals = $this->em->getRepository(Animal::class)->matching($criteria);
        return $animals;
    }
}
