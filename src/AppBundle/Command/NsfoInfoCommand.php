<?php

namespace AppBundle\Command;

use AppBundle\Service\CliOptionsService;
use AppBundle\Service\InfoService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoInfoCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('nsfo:info')
            ->setDescription(CliOptionsService::INFO_SYSTEM_SETTINGS)
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('app.info.parameters')->printInfo();
    }
}
