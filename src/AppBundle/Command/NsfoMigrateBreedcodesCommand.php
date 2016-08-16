<?php

namespace AppBundle\Command;

use AppBundle\Entity\Animal;
use AppBundle\Migration\BreedCodeReformatter;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoMigrateBreedcodesCommand extends ContainerAwareCommand
{
    const TITLE = 'Migrate breedcodes to values in separate variables for MiXBLUP';

    /** @var EntityManager $em */
    private $em;

    protected function configure()
    {
        $this
            ->setName('nsfo:migrate:breedcodes')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->em = $em;

        $reformatter = new BreedCodeReformatter($em);

        $output->writeln([
            '=== PROCESS FINISHED ===',
            '',
            '']);
    }

}
