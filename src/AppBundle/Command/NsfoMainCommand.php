<?php

namespace AppBundle\Command;

use AppBundle\Util\CommandUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class NsfoMainCommand
 */
class NsfoMainCommand extends ContainerAwareCommand
{
    const TITLE = 'OVERVIEW OF ALL NSFO COMMANDS';
    
    protected function configure()
    {
        $this
            ->setName('nsfo')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cmdUtil = new CommandUtil($input, $output, $this->getHelper('question'));
        $this->getContainer()->get('app.cli.options')->mainMenu($cmdUtil);
    }

}
