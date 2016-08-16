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

        //Timestamp
        $startTime = new \DateTime();
        $output->writeln(['Start time: '.date_format($startTime, 'Y-m-d h:m:s'),'']);

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->em = $em;


        $reformatter = new BreedCodeReformatter($em, false, new ArrayCollection());

        /** @var AnimalRepository $animalRepository */
        $animalRepository = $em->getRepository(Animal::class);
        $maxId = $animalRepository->getMaxId();
        $minId = $animalRepository->getMinIdOfAnimalsWithoutMixBlupBreedCode();

        for($i = $minId; $i <= $maxId; $i += self::BATCH_SIZE) {

            /** @var ArrayCollection $animals */
            $animals = $animalRepository->getAnimalsByIdWithoutMixBlupBreedCode($i, $i+self::BATCH_SIZE-1);
            if($animals->count() > 0) {
                $reformatter->setAnimals($animals);
                $reformatter->migrate();
            }
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

}
