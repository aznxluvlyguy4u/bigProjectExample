<?php

namespace AppBundle\Command;

use AppBundle\Util\CommandUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoTestCommand extends ContainerAwareCommand
{
    const TITLE = 'TESTING';
    const INPUT_PATH = '/path/to/file.txt';

    /** @var EntityManager $em */
    private $em;

    protected function configure()
    {
        $this
            ->setName('nsfo:test')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->em = $em;
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $cmdUtil->setStartTimeAndPrintIt();

//        $fileContents = file_get_contents(self::INPUT_PATH);

        $output->writeln([
            '=== Print Something ===',
            'Result 1: ',
            'Result 2: ',
            '',
            '']);

        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }

}
