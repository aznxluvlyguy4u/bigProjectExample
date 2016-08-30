<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\Location;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoMigrateMeasurements2016Command extends ContainerAwareCommand
{
    const TITLE = 'Migrate MeasurementsData for 2016: meetwaardenoverzicht2016.csv';
    const INPUT_PATH = '/home/data/JVT/projects/NSFO/Migratie/Animal/meetwaardenoverzicht2016.csv';

    /** @var ObjectManager $em */
    private $em;

    /** @var ArrayCollection */
    private $missingUbns;

    /** @var ArrayCollection */
    private $missingAnimals;

    protected function configure()
    {
        $this
            ->setName('nsfo:migrate:measurements2016')
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

        $this->missingUbns = new ArrayCollection();
        $this->missingAnimals = new ArrayCollection();

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $cmdUtil->setStartTimeAndPrintIt();

        $fileContents = file_get_contents(self::INPUT_PATH);

        $data = explode(PHP_EOL, $fileContents);
        $fileOutput = new ArrayCollection();
        $rowCount = 0;
        $animalNotFound = 0;
        $animalFound = 0;
        $animalMoreThanOne = 0;
        foreach ($data as $row) {
//            $output->writeln($row);

            //Skip the header row
            if($rowCount > 0) {
                $parts = explode(';',$row);
                $animalCount = $this->processRow($parts);

                if($animalCount == 0) {
                    $animalNotFound++;
                } elseif ($animalCount == 1) {
                    $animalFound++;
                } else {
                    $animalMoreThanOne++;
                }
            }

            $rowCount++;
        }

        $output->writeln([
            '=== Results ===',
            'AnimalNotFound: '.$animalNotFound,
            'AnimalFound: '.$animalFound,
            'AnimalMoreThanOne: '.$animalMoreThanOne,
            'Rows processed (incl header and empty rows): '.$rowCount,
            '']);

        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }
    

    /**
     * @param $rowParts
     */
    private function processRow($rowParts)
    {
        //null Check
        if(sizeof($rowParts) < 11) {
            return;
        }

        $ubn = $rowParts[0];
        $measurementDate = $rowParts[1];
        $animalOrderNumber = $rowParts[2];
        $pedigreeCode = $rowParts[3];
        $weight = $rowParts[4];
        $muscleThickness = $rowParts[5];
        $fat1 = $rowParts[6];
        $fat2 = $rowParts[7];
        $fat3 = $rowParts[8];
        $tailLength = $rowParts[9];
        $inspectorFullName = $rowParts[10];


        return $this->findAnimal($ubn, $animalOrderNumber);
    }


    private function findAnimal($ubn, $animalOrderNumber)
    {
        $location = $this->em->getRepository(Location::class)->findByUbn($ubn);

        if($location != null) {
            return 1;
        } else {
            return 0;
        }

        return sizeof($location);

        $foundAnimal = $this->em->getRepository(Animal::class)->findBy(['animalOrderNumber' => $animalOrderNumber, 'location' => $location]);

        return sizeof($foundAnimal);
//        if(sizeof($foundAnimal) )
    }
}
