<?php

namespace AppBundle\Command;

use AppBundle\Entity\Weight;
use AppBundle\Entity\WeightRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoFixBirthweightsCommand extends ContainerAwareCommand
{
    const TITLE = 'Set birthWeight measurementDate equal to DateOfBirth';
    const BATCH_SIZE = 1000;

    /** @var ObjectManager $em */
    private $em;

    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_out' => 'app/Resources/outputs/',
        'finder_name' => 'filename.csv',
        'ignoreFirstLine' => true
    );

    protected function configure()
    {
        $this
            ->setName('nsfo:fix:birthweights')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $helper = $this->getHelper('question');
        $cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));

        $isOnlyTestBirthWeights = $cmdUtil->generateConfirmationQuestion('Only test if DateOfBirth exists for all birthWeights? (y,n)');


        $totalBirthWeights = $this->getBirthWeightsCount();
        $startUnit = 0;

        $startMessage = 'Fix BirthWeights';
        $cmdUtil->setStartTimeAndPrintIt($totalBirthWeights, $startUnit, $startMessage);

        $counter = 0;

        if($isOnlyTestBirthWeights) {

            $sql = "SELECT animal.date_of_birth FROM weight INNER JOIN animal ON animal.id = weight.animal_id WHERE is_birth_weight = TRUE";
            $birthWeights = $em->getConnection()->query($sql)->fetchAll();

            foreach ($birthWeights as $birthWeight) {

                $dateOfBirth = $birthWeight['date_of_birth'];

                if($dateOfBirth == null || $dateOfBirth == '') {
                    $counter++;
                }

                $cmdUtil->advanceProgressBar(1, 'Checking for missing DateOfBirths...');

            }

            $cmdUtil->setProgressBarMessage('Missing Date of Births: '.$counter);
            $cmdUtil->setEndTimeAndPrintFinalOverview();

        } else {

            $sql = "SELECT weight.id as weight_id, animal.date_of_birth FROM weight INNER JOIN animal ON animal.id = weight.animal_id WHERE is_birth_weight = TRUE";
            $birthWeights = $em->getConnection()->query($sql)->fetchAll();

            foreach ($birthWeights as $birthWeight) {

                $measurementId = $birthWeight['weight_id'];
                $dateOfBirth = $birthWeight['date_of_birth'];
                $sql = "UPDATE measurement SET measurement_date = '". $dateOfBirth ."' WHERE id = '". $measurementId ."'";
                $em->getConnection()->exec($sql);

                $cmdUtil->advanceProgressBar(1, 'Set DateOfBirth for MeasurementDate...');
                $counter++;
            }

            $cmdUtil->setProgressBarMessage('Consider it done B)');
            $cmdUtil->setEndTimeAndPrintFinalOverview();

        }

    }


    /**
     * @return mixed|int
     */
    private function getBirthWeightsCount()
    {
        $sql = "SELECT COUNT(is_birth_weight) FROM weight WHERE is_birth_weight = TRUE";
        $results = $this->em->getConnection()->query($sql)->fetch();
        return $results['count'];
    }
}
