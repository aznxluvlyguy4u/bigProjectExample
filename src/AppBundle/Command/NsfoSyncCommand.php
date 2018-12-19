<?php

namespace AppBundle\Command;

use AppBundle\Entity\Employee;
use AppBundle\Service\AnimalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoSyncCommand extends ContainerAwareCommand
{
    const TITLE = 'Run sync processes intended for recurrent use with a cronjob';

    const ANIMAL_SYNC_MAX_DAYS = 0;

    const OPTION_RVO = 'rvo';
    const OPTION_WITHOUT_DELAY = 'without_delay';
    const OPTION_NO_MAX_ONCE_A_DAY = 'no_max_once_a_day';

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
            ->addOption(self::OPTION_RVO, 'r', InputOption::VALUE_NONE,
                'Is RVO leading')
            ->addOption(self::OPTION_NO_MAX_ONCE_A_DAY, 'd', InputOption::VALUE_NONE,
                'Auto sync can be sent more than once a day')
            ->addOption(self::OPTION_WITHOUT_DELAY, 'w', InputOption::VALUE_NONE,
                'Do not delay sending message by '.AnimalService::DEFAULT_ALL_SYNC_DELAY_IN_SECONDS.' seconds')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->animalService = $this->getContainer()->get('app.animal');
        $this->automatedProcess = $this->em->getRepository(Employee::class)->getAutomatedProcess();

        $isRvoLeading = $input->getOption(self::OPTION_RVO);
        $noMaxOnceADay = $input->getOption(self::OPTION_NO_MAX_ONCE_A_DAY);
        $sendWithoutDelays = $input->getOption(self::OPTION_WITHOUT_DELAY);
        $delayInSeconds = $sendWithoutDelays ? 0 : AnimalService::DEFAULT_ALL_SYNC_DELAY_IN_SECONDS;

        $this->syncAllNonSyncedLocations($isRvoLeading, $delayInSeconds, !$noMaxOnceADay);
    }


    /**
     * @param bool $isRvoLeading
     * @param bool $maxOnceADay
     * @param int $delayInSeconds
     * @throws \Exception
     */
    private function syncAllNonSyncedLocations(bool $isRvoLeading, int $delayInSeconds, bool $maxOnceADay)
    {
        $this->animalService->syncAnimalsForAllLocations($this->automatedProcess,
            self::ANIMAL_SYNC_MAX_DAYS,
            $isRvoLeading,
            $delayInSeconds,
            $maxOnceADay
            );
    }

}
