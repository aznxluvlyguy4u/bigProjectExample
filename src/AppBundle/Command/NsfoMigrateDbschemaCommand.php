<?php

namespace AppBundle\Command;

use AppBundle\Migration\HiddenMessage;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoMigrateDbschemaCommand extends ContainerAwareCommand
{
    const TITLE = 'Migrate data during a database schema change';
    const DEFAULT_OPTION = 0;

    /** @var ObjectManager $em */
    private $em;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var OutputInterface */
    private $output;

    protected function configure()
    {
        $this
            ->setName('nsfo:migrate:dbschema')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $this->output = $output;
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Copy isRemoveByUser from DeclareBaseReponse to hideFailedMessage in DeclareBase', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1:
                HiddenMessage::migrateHiddenMessageStatusFromResponseToRequestBase($em, $this->cmdUtil);
                $output->writeln('DONE!');
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }
    }

}
