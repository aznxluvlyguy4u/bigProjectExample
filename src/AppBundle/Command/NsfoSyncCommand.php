<?php

namespace AppBundle\Command;

use AppBundle\Entity\Employee;
use AppBundle\Service\AnimalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoSyncCommand extends ContainerAwareCommand
{
    const TITLE = 'Run sync processes intended for recurrent use with a cronjob';

    const ANIMAL_SYNC_MAX_DAYS = 7;

    /** @var EntityManagerInterface */
    private $em;
    /** @var AnimalService */
    private $animalService;
    /** @var Employee */
    private $automatedProcess;

    protected function configure()
    {
        $this
            ->setName('nsfo:sync')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->animalService = $this->getContainer()->get('app.animal');
        $this->automatedProcess = $this->em->getRepository(Employee::class)->getAutomatedProcess();

        $this->syncAllNonSyncedLocations();
    }


    private function syncAllNonSyncedLocations()
    {
        $this->animalService->syncAnimalsForAllLocations($this->automatedProcess,
            self::ANIMAL_SYNC_MAX_DAYS);
    }

}
