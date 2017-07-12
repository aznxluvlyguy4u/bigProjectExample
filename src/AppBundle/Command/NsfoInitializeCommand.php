<?php

namespace AppBundle\Command;

use AppBundle\Cache\AnimalCacher;
use AppBundle\Cache\ExteriorCacher;
use AppBundle\Cache\GeneDiversityUpdater;
use AppBundle\Cache\NLingCacher;
use AppBundle\Cache\ProductionCacher;
use AppBundle\Cache\WeightCacher;
use AppBundle\Entity\BirthProgress;
use AppBundle\Migration\BirthProgressMigrator;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\LitterUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoInitializeCommand extends ContainerAwareCommand
{
    const TITLE = 'Initialze database values';
    const DEFAULT_OPTION = 0;

    /** @var ObjectManager $em */
    private $em;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var Connection */
    private $conn;

    /** @var string */
    private $rootDir;

    protected function configure()
    {
        $this
            ->setName('nsfo:initialize')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $this->conn = $em->getConnection();
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);
        $this->rootDir = $this->getContainer()->get('kernel')->getRootDir();

        $output->writeln(['',DoctrineUtil::getDatabaseHostAndNameString($em),'']);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Initialize database values for... ', "\n",
            '1: BirthProgress', "\n\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1:
                $migrator = new BirthProgressMigrator($this->cmdUtil, $this->em, $this->rootDir);
                $migrator->migrate();
                $output->writeln('DONE!');
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }
    }

}
