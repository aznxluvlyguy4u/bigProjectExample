<?php

namespace AppBundle\Command;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Util\CommandUtil;
use Doctrine\ORM\EntityManager;
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
         * @var EntityManager $em
         */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
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
            $sql = "UPDATE animal SET type='Ram' WHERE id = ". $result['id'];
            $em->getConnection()->exec($sql);

            $sql = "SELECT id FROM ram WHERE id = ". $result['id'];
            $result = $em->getConnection()->query($sql)->fetch();

            if($result['id'] == '' || $result['id'] == null) {
                $sql = "INSERT INTO ram VALUES (" . $result['id'] . ", 'Ram')";
                $em->getConnection()->exec($sql);
            }

            $sql = "SELECT id FROM neuter WHERE id = ". $result['id'];
            $result = $em->getConnection()->query($sql)->fetch();

            if($result['id'] != '' || $result['id'] != null) {
                $sql = "DELETE FROM neuter WHERE id = " . $result['id'];
                $em->getConnection()->exec($sql);
            }
            $counterMale++;
        }


        $sql = "SELECT DISTINCT mother.id FROM animal INNER JOIN animal AS mother ON animal.parent_mother_id = mother.id WHERE mother.type <> 'Ewe'";
        $results = $em->getConnection()->query($sql)->fetchAll();

        $counterFemale = 0;
        foreach ($results as $result) {
            $sql = "UPDATE animal SET type='Ewe' WHERE id = ". $result['id'];
            $em->getConnection()->exec($sql);

            $sql = "SELECT id FROM ewe WHERE id = ". $result['id'];
            $result = $em->getConnection()->query($sql)->fetch();

            if($result['id'] == '' || $result['id'] == null) {
                $sql = "INSERT INTO ewe VALUES (" . $result['id'] . ", 'Ewe')";
                $em->getConnection()->exec($sql);
            }

            $sql = "SELECT id FROM neuter WHERE id = ". $result['id'];
            $result = $em->getConnection()->query($sql)->fetch();

            if($result['id'] != '' || $result['id'] != null) {
                $sql = "DELETE FROM neuter WHERE id = " . $result['id'];
                $em->getConnection()->exec($sql);
            }
            $counterFemale++;
        }

        $cmdUtil->setEndTimeAndPrintFinalOverview();
        $output->writeln(["ANIMALS CHANGED: " . ($counterMale + $counterFemale),
            "RAMS CHANGED: ".$counterMale,
            "EWES CHANGED: ".$counterFemale
        ]);
    }
}
