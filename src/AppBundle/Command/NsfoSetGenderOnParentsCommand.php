<?php

namespace AppBundle\Command;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\GenderChanger;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class NsfoSetGenderOnParentsCommand extends ContainerAwareCommand
{
    const TITLE = 'SET Gender of Parents';

    protected function configure()
    {
        $this
            ->setName('nsfo:setGenderOnParents')
            ->setDescription(self::TITLE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var ObjectManager $em
         */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);
        $counter = 0;

        $cmdUtil->setStartTimeAndPrintIt();

        $counter++;

        if ($counter % 1000 == 0) {
            $output->writeln($counter);
        }

        $sql = "SELECT DISTINCT father.id FROM animal INNER JOIN animal AS father ON animal.parent_father_id = father.id WHERE father.type <> 'Ram'";
        $results = $em->getConnection()->query($sql)->fetchAll();


        $counterMale = 0;
        foreach ($results as $result) {
            GenderChanger::changeNeuterToMaleBySql($em, $result['id']);
            $counterMale++;
        }


        $sql = "SELECT DISTINCT mother.id FROM animal INNER JOIN animal AS mother ON animal.parent_mother_id = mother.id WHERE mother.type <> 'Ewe'";
        $results = $em->getConnection()->query($sql)->fetchAll();

        $counterFemale = 0;
        foreach ($results as $result) {
            GenderChanger::changeNeuterToFemaleBySql($em, $result['id']);
            $counterFemale++;
        }

        $cmdUtil->setEndTimeAndPrintFinalOverview();
        $output->writeln(["ANIMALS CHANGED: " . ($counterMale + $counterFemale),
            "RAMS CHANGED: ".$counterMale,
            "EWES CHANGED: ".$counterFemale
        ]);
    }
}
