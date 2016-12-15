<?php

namespace AppBundle\Command;

use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Inspector;
use AppBundle\Entity\InspectorAuthorization;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Enumerator\InspectorMeasurementType;
use AppBundle\Migration\DeclareLossMigrator;
use AppBundle\Migration\InspectorMigrator;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoFixDeclaresCommand extends ContainerAwareCommand
{
    const TITLE = 'Fix Declares';
    const DEFAULT_OPTION = 0;

    /** @var ObjectManager $em */
    private $em;

    /** @var Connection $conn */
    private $conn;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var OutputInterface */
    private $output;


    protected function configure()
    {
        $this
            ->setName('nsfo:fix:declares')
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
        $this->output = $output;

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Fix alive animals or animals without dateOfDeath but with finished declareLosses', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {

            case 1:
                $status = DeclareLossMigrator::fixAnimalDateOfDeathAndAliveStateByDeclareLossStatus($em, $this->cmdUtil);
                $result = $status ? 'Fixed any mismatched animal death data!'
                                    :'Some animals have revoked declareLosses. Improve the command to _deal with it_ B)' ;
                $output->writeln($result);
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }

    }

}
