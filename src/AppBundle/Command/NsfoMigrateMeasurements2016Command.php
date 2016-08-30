<?php

namespace AppBundle\Command;

use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\GenderType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\StringUtil;
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

    /** @var AnimalRepository */
    private $animalRepository;
    
    /** @var int */
    private $foundByPedigreeCode;
    
    /** @var int */
    private $foundByUbnAndAnimalOrderNumber;

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

        $this->animalRepository = $em->getRepository(Animal::class);

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
        foreach ($data as $row) {

            //Skip the header row
            if($rowCount > 0) {
                $parts = explode(';',$row);
                $animalCount = $this->processRow($parts);

                if($animalCount == 0) {
                    $animalNotFound++;
                } else {
                    $animalFound++;
                }
            }

            $rowCount++;
            $output->write('|');
        }

        $output->writeln(['===============','Missing ubns: ']);
        foreach ($this->missingUbns as $ubn) {
            $output->writeln($ubn);
        }
        $output->writeln('===============');

        $output->writeln('Missing pedigreeCodes: ');
        foreach ($this->missingAnimals as $pedigreeCode) {
            $output->writeln($pedigreeCode);
        }
        $output->writeln('===============');

        $output->writeln([
            '=== Results ===',
            'AnimalNotFound: '.$animalNotFound,
            'AnimalFound: '.$animalFound,
            'Rows processed (incl header and empty rows): '.$rowCount,
            '']);

        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }
    

    /**
     * @param array $rowParts
     */
    private function processRow($rowParts)
    {
        //null Check
        if(sizeof($rowParts) < 11) {
            return;
        }

        $ubn = $rowParts[0];
        $measurementDate = new \DateTime($rowParts[1]);
        $animalOrderNumber = strval(sprintf('%05d', $rowParts[2]));
        $pedigreeCode = $rowParts[3];
        $weight = $rowParts[4];
        $muscleThickness = $rowParts[5];
        $fat1 = $rowParts[6];
        $fat2 = $rowParts[7];
        $fat3 = $rowParts[8];
        $tailLength = $rowParts[9];
        $inspectorFullName = $rowParts[10];

        $foundAnimal = $this->findAnimalByPedigreeCode($pedigreeCode);
        
        if($foundAnimal != null) {
            $this->foundByPedigreeCode++;
        } else {
            $foundAnimal = $this->findAnimalByUbnAndAnimalOrder($ubn, $animalOrderNumber);
            if($foundAnimal != null) {
                $this->foundByUbnAndAnimalOrderNumber++;
            }
        }
        $this->missingAnimals->set($pedigreeCode, $ubn. ';' . $pedigreeCode. ';' .$animalOrderNumber);

        
    }


    /**
     * @param $pedigreeCode
     * @return Animal|Ewe|Neuter|Ram|null
     */
    private function findAnimalByPedigreeCode($pedigreeCode)
    {
        $pedigreeCodeParts = StringUtil::getStnFromCsvFileString($pedigreeCode);

        return $this->animalRepository->findByPedigreeCountryCodeAndNumber(
            $pedigreeCodeParts[JsonInputConstant::PEDIGREE_COUNTRY_CODE],
            $pedigreeCodeParts[JsonInputConstant::PEDIGREE_NUMBER]
        );
    }


    /**
     * @param $ubn
     * @param $animalOrderNumber
     * @return Animal|Ewe|Neuter|Ram|null
     */
    private function findAnimalByUbnAndAnimalOrder($ubn, $animalOrderNumber)
    {
        $location = $this->em->getRepository(Location::class)->findByUbn($ubn);

        if($location != null) {
            return $this->em->getRepository(Animal::class)->findOneBy(['animalOrderNumber' => $animalOrderNumber, 'location' => $location]);
        } else {
            $this->missingUbns->set($ubn, $ubn);
        }
        return null;
    }
}
