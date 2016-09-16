<?php

namespace AppBundle\Command;

use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoFixGendersCommand extends ContainerAwareCommand
{
    const TITLE = 'Fix wrong (Neuter/Ewe/Ram) type in Animal in combination with Neuter, Ram or Ewe Entity';
    const INPUT_PATH = '/path/to/file.txt';

    /** @var ObjectManager $em */
    private $em;

    /** @var OutputInterface */
    private $output;

    protected function configure()
    {
        $this
            ->setName('nsfo:fix:genders')
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
        $cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        /* Diagnosis */

        $sql = "SELECT animal.id, ram.object_type, animal.type FROM animal INNER JOIN ram ON animal.id = ram.id WHERE ram.object_type <> animal.type";
        $resultsRam = $this->em->getConnection()->query($sql)->fetchAll();
        $this->printDiagnosisResults($resultsRam, 'Ram');

        $sql = "SELECT animal.id, ewe.object_type, animal.type FROM animal INNER JOIN ewe ON animal.id = ewe.id WHERE ewe.object_type <> animal.type";
        $resultsEwe = $this->em->getConnection()->query($sql)->fetchAll();
        $this->printDiagnosisResults($resultsEwe, 'Ewe');

        $sql = "SELECT animal.id, neuter.object_type, animal.type FROM animal INNER JOIN neuter ON animal.id = neuter.id WHERE neuter.object_type <> animal.type";
        $resultsNeuter = $this->em->getConnection()->query($sql)->fetchAll();
        $this->printDiagnosisResults($resultsNeuter, 'Neuter');

        $output->writeln([' ', 'NOTE! RERUN THIS COMMAND AFTER EVERY DUPLICATE KEY VIOLATION ERROR' ,' ']);

        $cmdUtil->setStartTimeAndPrintIt(count($resultsRam) + count($resultsEwe), 0);

        if(count($resultsRam) > 0) {
            /* Fix animals incorrectly being a Ram */
            foreach($resultsRam as $ramResult) {
                $id = $ramResult['id'];
                if($ramResult['type'] == 'Neuter'){
                    $sql = "DELETE FROM ram WHERE id = '".$id."'";
                    $this->em->getConnection()->exec($sql);
                    $sql = "INSERT INTO neuter (id, object_type) VALUES ('".$id."', 'Neuter')";
                    $this->em->getConnection()->exec($sql);

                } elseif($ramResult['type'] == 'Ewe'){
                    $sql = "DELETE FROM ram WHERE id = '".$id."'";
                    $this->em->getConnection()->exec($sql);
                    $sql = "INSERT INTO ewe (id, object_type) VALUES ('".$id."', 'Ewe')";
                    $this->em->getConnection()->exec($sql);
                }
                $cmdUtil->advanceProgressBar(1, 'Incorrect Ram with id: '.$id.'');
            }
        }

        if(count($resultsEwe) > 0) {
            /* Fix animals incorrectly being a Ewe */
            foreach($resultsEwe as $eweResult) {
                $id = $eweResult['id'];
                if($eweResult['type'] == 'Neuter'){
                    $sql = "DELETE FROM ewe WHERE id = '".$id."'";
                    $this->em->getConnection()->exec($sql);
                    $sql = "INSERT INTO neuter (id, object_type) VALUES ('".$id."', 'Neuter')";
                    $this->em->getConnection()->exec($sql);

                } elseif($eweResult['type'] == 'Ram'){
                    $sql = "DELETE FROM ewe WHERE id = '".$id."'";
                    $this->em->getConnection()->exec($sql);
                    $sql = "INSERT INTO ram (id, object_type) VALUES ('".$id."', 'Ram')";
                    $this->em->getConnection()->exec($sql);
                }
                $cmdUtil->advanceProgressBar(1, 'Incorrect Ewe with id: '.$id.'');
            }
        }


        if(count($resultsNeuter) > 0) {
            /* No animals are incorrectly being a Neuter at the moment */
            $output->writeln('Write fix for neuters');
        }


        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    public function printDiagnosisResults($results, $entityType)
    {
        $neuterCount = 0;
        $eweCount = 0;
        $ramCount = 0;
        foreach ($results as $result) {
            if($result['type'] == 'Ewe') { $eweCount++; }
            elseif($result['type'] == 'Neuter') { $neuterCount++; }
            elseif($result['type'] == 'Ram') { $ramCount++; }
        }

        $this->output->writeln([
            ' ',
            '=== '.$entityType.' Entities ===',
            'Total: '.count($results)
        ]);

        if($entityType != 'Ewe') {
            $this->output->writeln('As Ewe type in Animal: '.$eweCount);
        }

        if($entityType != 'Ram') {
            $this->output->writeln('As Ram type in Animal: '.$ramCount);
        }

        if($entityType != 'Neuter') {
            $this->output->writeln('As Neuter type in Animal: '.$neuterCount);
        }

        $this->output->writeln('-------------------------');
    }

}
