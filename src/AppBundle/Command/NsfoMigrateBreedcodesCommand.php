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

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->em = $em;


        $reformatter = new BreedCodeReformatter($em, false, new ArrayCollection());

        /** @var AnimalRepository $animalRepository */
        $animalRepository = $em->getRepository(Animal::class);
        $maxId = $animalRepository->getMaxId();
        $minId = $animalRepository->getMinIdOfAnimalsWithoutBreedCodesSetForExistingBreedCode();

        $cmdUtil->setStartTimeAndPrintIt($maxId, $minId, 'Data retrieved from database. Now generating breedcodes...');

        for($i = $minId; $i <= $maxId; $i += self::BATCH_SIZE) {

            /** @var ArrayCollection $animals */
            $animals = $animalRepository->getAnimalsByIdWithoutBreedCodesSetForExistingBreedCode($i, $i+self::BATCH_SIZE-1);
            if($animals->count() > 0) {
                $reformatter->setAnimals($animals);
                $reformatter->migrate();

                $message = 'Processed: '.$i.' - '.($i+self::BATCH_SIZE-1).' of '.$maxId;
                for($j = 0; $j < self::BATCH_SIZE; $j++) {
                    $cmdUtil->advanceProgressBar(1, $message);
                }
            }
        }

        if($minId == null) {
            $output->writeln('All animals with a breedcode already have been processed');
        } else {
            $cmdUtil->setProgressBarMessage('Processed Total: '.$minId.' - '.$maxId.' of '.$maxId);
        }

        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }

}
