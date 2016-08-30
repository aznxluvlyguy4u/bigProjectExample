<?php

namespace AppBundle\Command;

use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Tests\Compiler\A;

class NsfoReadStnCommand extends ContainerAwareCommand
{
    const TITLE = 'Read PedigreeNumbers (STN)';
    const DEFAULT_INPUT_PATH = '/home/data/JVT/projects/NSFO/Migratie/Animal/diergegevens/DiertabelNieuw.csv';
    const DEFAULT_VSM_START_ID = 360000;//1;
    const ANIMAL_BATCH_SIZE = 1000;

    /** @var ArrayCollection $pedigreeCodesByVsmId */
    private $pedigreeCodesByVsmId;

    /** @var ObjectManager $em */
    private $em;

    protected function configure()
    {
        $this
            ->setName('nsfo:read:stn')
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

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        //Input folder input
        $inputFolderPath = $cmdUtil->generateQuestion('Please enter input folder path: ', self::DEFAULT_INPUT_PATH);
        $minId = $cmdUtil->generateQuestion('Please enter min primaryKeyId for retrieving animals: ', self::DEFAULT_VSM_START_ID);

        $cmdUtil->setStartTimeAndPrintIt();

        $dataWithoutHeader = $cmdUtil::getRowsFromCsvFileWithoutHeader($inputFolderPath);

        $rowCount = 0;
        $this->pedigreeCodesByVsmId = new ArrayCollection();
        //First save pedigree date per vsmId in an ArrayCollection
        foreach ($dataWithoutHeader as $row) {

            $this->savePedigeeCodes($row);
            $rowCount++;
            if($rowCount%10000 == 0) {
                $output->writeln('PedigreeCodes processed into array: '.$rowCount);
            }
        }
        $output->writeln('PedigreeCodes processed into array: '.$rowCount);





        /** @var AnimalRepository $animalRepository */
        $animalRepository = $em->getRepository(Animal::class);
        $maxId = $animalRepository->getMaxId();
        $output->writeln('maxId: '.$maxId);

        $animalCount = 0;
        for($i = $minId; $i <= $maxId; $i += self::ANIMAL_BATCH_SIZE) {

            $maxIdInBatch = $i + self::ANIMAL_BATCH_SIZE-1;

            /** @var ArrayCollection $animals */
            $output->write('Find animals with primaryKeys between: '.$i.' - '.($maxIdInBatch).' of '.$maxId.'   ');
            $animals = $this->findAnimalsWithAVsmIdBetweenPrimaryKeys($i, $maxIdInBatch);
            $output->writeln('Animals found, processing animals...');
            if($animals->count() > 0) {

                /** @var Animal $animal */
                foreach($animals as $animal) {
                    $vsmId = $animal->getName();
                    $stnParts = Utils::getNullCheckedArrayCollectionValue($vsmId, $this->pedigreeCodesByVsmId);
                    if($stnParts != null) {
                        $animal->setPedigreeCountryCode($stnParts[JsonInputConstant::PEDIGREE_COUNTRY_CODE]);
                        $animal->setPedigreeNumber($stnParts[JsonInputConstant::PEDIGREE_NUMBER]);

                        $em->persist($animal);
                        $animalCount++;
                    }
                }
                DoctrineUtil::flushClearAndGarbageCollect($em);
                $output->writeln('Processed primaryKeys: '.$i.' - '.($maxIdInBatch).' of '.$maxId);
            }

//            if ($i >= self::MAX_ROWS_TO_PROCESS) {
//                break;
//            }
        }

        if($minId == null) {
            $output->writeln('All animals already have been processed');
        } else {
            $output->writeln('Processed Total: '.$minId.' - '.$maxId.' of '.$maxId);
        }
        $output->writeln('Animals processed: '.$animalCount);

        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    /**
     * @param int $minVsmId
     * @param int $maxVsmId
     * @return mixed
     */
    private function findAnimalsWithAVsmIdBetweenPrimaryKeys($minVsmId, $maxVsmId)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->neq('name', null))
            ->andWhere(Criteria::expr()->gte('id', $minVsmId))
            ->andWhere(Criteria::expr()->lte('id', $maxVsmId))
        ;

        return $this->em->getRepository(Animal::class)
            ->matching($criteria);
    }


    private function savePedigeeCodes($row)
    {
        //null checks
        if($row == null || $row == '') { return; }
        $rowParts = explode(';',$row);

        if(sizeof($rowParts) < 12) { return; }

        $vsmId = $rowParts[0];
        $pedigreeCode = $rowParts[1];

        $stnParts = StringUtil::getStnFromCsvFileString($pedigreeCode);

        $this->pedigreeCodesByVsmId->set($vsmId, $stnParts);
    }



    /**
     * @param $rowParts
     */
    private function processRow($row)
    {
        //null checks
        if($row == null || $row == '') { return; }
        $rowParts = explode(';',$row);

        if(sizeof($rowParts) < 12) { return; }



        $vsmId = $rowParts[0];
        $pedigreeCode = $rowParts[1];
//        $animalOrderNumber = $rowParts[2];
//        $uln = $rowParts[3];
//        $vsmIdFather = $rowParts[4];
//        $vsmIdMother = $rowParts[5];
//        $gender = $rowParts[6];
//        $dateOfBirth = $rowParts[7];
//        $breedCode = $rowParts[8];
//        $ubnBreeder = $rowParts[9];
//        $pedigreeOfRegistration = $rowParts[10];
//        $breedType = $rowParts[11];
//        $scrapieGenotype = $rowParts[12];

//        $stnParts = StringUtil::getStnFromCsvFileString($pedigreeCode);
//        $pedigreeCountryCode = $stnParts[JsonInputConstant::PEDIGREE_COUNTRY_CODE];
//        $pedigreeNumber = $stnParts[JsonInputConstant::PEDIGREE_NUMBER];
//
//        dump($pedigreeCountryCode, $pedigreeNumber);die;

        return $this->findAnimal($vsmId);
    }
}
