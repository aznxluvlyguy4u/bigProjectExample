<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class NsfoMigrateBreedvalues2016Command extends ContainerAwareCommand
{
    const TITLE = 'Migrate Mixblup breedvalue output fo 2016 from Relani.out and Solani.out files';
    const ROWS_IN_SOURCE_FILE = 608983;
    const GENERATION_DATA_STRING = '2016-10-04 00:00:00';
    const IS_PERSIST_ALSO_UNRELIABLE_BREEDVALUE = true;

    /** @var ObjectManager $em */
    private $em;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var AnimalRepository */
    private $animalRepository;

    /** @var array */
    private $relani;

    /** @var array */
    private $solani;

    /** @var ArrayCollection */
    private $animalIdByUln;

    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/MixblupBreedValues2016_10_04',
        'finder_name_solani' => 'Solani.out',
        'finder_name_relani' => 'Relani.out',
        'ignoreFirstLine' => false
    );

    protected function configure()
    {
        $this
            ->setName('nsfo:migrate:breedvalues2016')
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
        $this->animalRepository = $this->em->getRepository(Animal::class);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        if($this->cmdUtil->generateConfirmationQuestion('Only check if animals in Rolani.out and Solani.out match? (y/n)')) {
            $this->checkIfBothFilesContainTheSameAnimals();
        }

        //Create searchArrays
        $this->animalIdByUln = $this->animalRepository->getAnimalPrimaryKeysByUlnString(false);
        $this->parseInputFiles();

        //Write to database
        $this->migrateRecords();
    }


    private function migrateRecords()
    {
        $this->cmdUtil->setStartTimeAndPrintIt(count($this->relani)+1, 1, 'Migrating records...');

        $alreadyProcessedAnimalIds = $this->getAnimalIdOfAlreadyProcessedRecords();

        $logDate = new \DateTime();
        $logDateString = $logDate->format('Y-m-d H:i:s');

        $animalIds = array_keys($this->relani);
        foreach ($animalIds as $animalId)
        {
            //Only write unprocessed records to the database to prevent duplicates!
            if(!array_key_exists($animalId, $alreadyProcessedAnimalIds)) {
                //Collect data per animal
                $relaniParts = $this->relani[$animalId];

//            $uln = $relaniParts[1];
//            $descendantsInPedigree = $relaniParts[2];
//            $observationsInData = $relaniParts[3];
                $muscleThicknessReliability = floatval($relaniParts[4]);
                $growthReliability = floatval($relaniParts[5]);
                $fatReliability = floatval($relaniParts[6]);

                $isMuscleThicknessReliable = NullChecker::floatIsNotZero($muscleThicknessReliability);
                $isGrowthReliable = NullChecker::floatIsNotZero($growthReliability);
                $isFatReliable = NullChecker::floatIsNotZero($fatReliability);

                $solaniParts = $this->solani[$animalId];

//            $descendants = $solaniParts[1];
//            $observations = $solaniParts[2];

                //Null values cannot be saved to the database. Null is checked by reliability value
                $muscleThickness = floatval($solaniParts[3]);
                $growth = floatval($solaniParts[4]); //kg/day
                $fat = floatval($solaniParts[5]);


                if(self::IS_PERSIST_ALSO_UNRELIABLE_BREEDVALUE) {
                    $isSaveToDatabase = true;
                } else {
                    //Only create a new record if at least one trait is reliable
                    $isSaveToDatabase = $isMuscleThicknessReliable || $isGrowthReliable || $isFatReliable;
                }


                if($isSaveToDatabase) {
                    //Write to database
                    $sql = "SELECT MAX(id) FROM breed_values_set";
                    $foundId = $this->em->getConnection()->query($sql)->fetch()['max'];
                    if ($foundId == null) {
                        $id = 1;
                    } else {
                        $id = $foundId + 1;
                    }

                    $values = $id . "," . $animalId . ",'" . $logDateString . "','" . self::GENERATION_DATA_STRING . "'," . $muscleThickness . ',' . $growth . ',' . $fat . ',' . $muscleThicknessReliability . ',' . $growthReliability . ',' . $fatReliability;

                    $sql = "INSERT INTO breed_values_set (id, animal_id, log_date, generation_date, muscle_thickness, growth,
                    fat, muscle_thickness_reliability, growth_reliability, fat_reliability)
                    VALUES (" . $values . ")";
                    $this->em->getConnection()->exec($sql);
                }
            }

            $this->cmdUtil->advanceProgressBar(1);
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    private function parseInputFiles()
    {
        $message = 'Parsing Relani.out';
        $this->cmdUtil->setStartTimeAndPrintIt(5, 1, $message);
        $relaniRows = $this->parseCSV('finder_name_relani');

        $this->cmdUtil->advanceProgressBar(1, 'Splitting Relani.out columns');
        foreach($relaniRows as $relaniRow) {
            $relaniParts = explode(' ', StringUtil::replaceMultipleSpacesByOne($relaniRow)[0]);
            $animalId = $this->animalIdByUln->get($relaniParts[1]);
            $this->relani[$animalId] = $relaniParts;
        }

        $this->cmdUtil->setProgressBarMessage('Parsing Solani.out');
        $solaniRows = $this->parseCSV('finder_name_solani');

        $this->cmdUtil->advanceProgressBar(1, 'Splitting Solani.out columns');
        foreach($solaniRows as $solaniRow) {
            $solaniParts = explode(' ', StringUtil::replaceMultipleSpacesByOne($solaniRow)[0]);
            $animalId = $this->animalIdByUln->get($solaniParts[0]);
            $this->solani[$animalId] = $solaniParts;
        }

        $this->cmdUtil->setProgressBarMessage('Parsing complete!');
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }

    /**
     * @param string $fileName
     * @return array
     */
    private function parseCSV($fileName) {
        $ignoreFirstLine = $this->csvParsingOptions['ignoreFirstLine'];

        $finder = new Finder();
        $finder->files()
            ->in($this->csvParsingOptions['finder_in'])
            ->name($this->csvParsingOptions[$fileName])
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


    private function checkIfBothFilesContainTheSameAnimals()
    {
        $animalIdsOfRelani = array_keys($this->relani);
        foreach ($animalIdsOfRelani as $animalIdOfRelani)
        {
            if(!array_key_exists($animalIdOfRelani, $this->solani))
            {
                dump($animalIdOfRelani);
            }
        }
        dump(
            'CHECK DONE!'
        );die;
    }


    /**
     * @return array
     */
    private function getAnimalIdOfAlreadyProcessedRecords()
    {
        $animalIds = array();
        $sql = "SELECT animal_id FROM breed_values_set WHERE generation_date = '".self::GENERATION_DATA_STRING."'";
        $results = $this->em->getConnection()->query($sql)->fetchAll();
        foreach ($results as $result)
        {
            $animalId = $result['animal_id'];
            $animalIds[$animalId] = $animalId;
        }
        return $animalIds;
    }
}
