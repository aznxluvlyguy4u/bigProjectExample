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

//        $fileContents = file_get_contents(self::INPUT_PATH);




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
