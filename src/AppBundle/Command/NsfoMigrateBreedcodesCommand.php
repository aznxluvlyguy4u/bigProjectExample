<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Migration\BreedCodeReformatter;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoMigrateBreedcodesCommand extends ContainerAwareCommand
{
    const TITLE = 'Migrate breedcodes to values in separate variables for MiXBLUP';
    const BATCH_SIZE = 1000;
    const MAX_ROWS_TO_PROCESS = 100000000000000;

    /** @var EntityManager $em */
    private $em;

    protected function configure()
    {
        $this
            ->setName('nsfo:migrate:breedcodes')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $cmdUtil->setStartTimeAndPrintIt();

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->em = $em;


        $reformatter = new BreedCodeReformatter($em, false, new ArrayCollection());

        /** @var AnimalRepository $animalRepository */
        $animalRepository = $em->getRepository(Animal::class);
        $maxId = $animalRepository->getMaxId();
        $minId = $animalRepository->getMinIdOfAnimalsWithoutBreedCodesSetForExistingBreedCode();

//        dump($animalRepository->getAnimalsByIdWithoutBreedCodesSetForExistingBreedCode(1, 6000000)->count());die;

        for($i = $minId; $i <= $maxId; $i += self::BATCH_SIZE) {

            /** @var ArrayCollection $animals */
            $animals = $animalRepository->getAnimalsByIdWithoutBreedCodesSetForExistingBreedCode($i, $i+self::BATCH_SIZE-1);
            if($animals->count() > 0) {
                $reformatter->setAnimals($animals);
                $reformatter->migrate();
                $output->writeln('Processed: '.$i.' - '.($i+self::BATCH_SIZE-1).' of '.$maxId);
            }

//            if ($i >= self::MAX_ROWS_TO_PROCESS) {
//                break;
//            }
        }
        $output->writeln('Processed: '.$i.' - '.$maxId.' of '.$maxId);

        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }

}
