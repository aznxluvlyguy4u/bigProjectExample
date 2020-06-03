<?php

namespace AppBundle\Command;

use AppBundle\Service\DataFix\UbnHistoryFixer;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoResidencesFixCommand extends ContainerAwareCommand
{
    const TITLE = 'FIX ALL ANIMAL RESIDENCES BY CURRENT LOCATION OF ANIMALS';

    protected function configure()
    {
        $this
            ->setName('nsfo:residences:fix')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var UbnHistoryFixer $ubnHistoryFixer */
        $ubnHistoryFixer = $this->getContainer()->get(UbnHistoryFixer::class);
        $ubnHistoryFixer->fixAllHistoricAnimalResidenceRecordsByCurrentAnimalLocation();
    }

}
