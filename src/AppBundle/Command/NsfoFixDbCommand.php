<?php

namespace AppBundle\Command;

use AppBundle\Util\CommandUtil;
use AppBundle\Util\DatabaseDataFixer;
use AppBundle\Util\DoctrineUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoFixDbCommand extends ContainerAwareCommand
{
    const TITLE = 'Fix database data command';
    const DEFAULT_OPTION = 0;
    
    /** @var CommandUtil */
    private $cmdUtil;

    /** @var OutputInterface */
    private $output;

    /** @var ObjectManager */
    private $em;

    /** @var Connection */
    private $conn;
    
    protected function configure()
    {
        $this
            ->setName('nsfo:fix:db')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->conn = $this->em->getConnection();
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);
        $this->output = $output;

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));
        $output->writeln([DoctrineUtil::getDatabaseHostAndNameString($this->em),'']);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '=====================================', "\n",
            '1: Update MaxId of all sequences', "\n",
            '=====================================', "\n",
            '2: Fill missing animalOrderNumbers', "\n",
            '3: Fix incongruent animalOrderNumbers', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1:
                DatabaseDataFixer::updateMaxIdOfAllSequences($this->conn, $this->cmdUtil);
                $output->writeln('Done!');
                break;

            case 2:
                DatabaseDataFixer::fillMissingAnimalOrderNumbers($this->conn, $this->cmdUtil);
                $output->writeln('Done!');
                break;

            case 3:
                DatabaseDataFixer::fixIncongruentAnimalOrderNumbers($this->conn, $this->cmdUtil);
                $output->writeln('Done!');
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }
    }
    

}
