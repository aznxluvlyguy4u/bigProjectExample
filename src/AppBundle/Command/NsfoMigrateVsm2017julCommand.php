<?php

namespace AppBundle\Command;

use AppBundle\Util\CommandUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class NsfoMigrateVsm2017julCommand
 */
class NsfoMigrateVsm2017julCommand extends ContainerAwareCommand
{
    const TITLE = 'Migrating VSM data for 2017 June & WormResistance';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('nsfo:migrate:vsm')
            ->setDescription(self::TITLE);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cmdUtil = new CommandUtil($input, $output, $this->getHelper('question'));
        $this->getContainer()->get('app.migrator.vsm')->run($cmdUtil);
    }
}
