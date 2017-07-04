<?php

namespace AppBundle\Command;

use AppBundle\Util\CommandUtil;
use AppBundle\Util\DatabaseDataFixer;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\LitterUtil;
use AppBundle\Util\MeasurementsUtil;
use AppBundle\Validation\AscendantValidator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Monolog\Logger;
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
    /** @var Logger */
    private $logger;
    
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
        $this->logger = $this->getContainer()->get('logger');

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
            '4: Fix incongruent animalIdAndDate values in measurement table', "\n",
            '5: Fix duplicate litters only containing stillborns', "\n",
            '6: Find animals with themselves being their own ascendant', "\n",
            '7: Print from database, animals with themselves being their own ascendant', "\n",
            '8: Fill missing breedCodes and set breedCode = breedCodeParents if both parents have the same pure (XX100) breedCode', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);

        $ascendantValidator = new AscendantValidator($this->em, $this->cmdUtil, $this->logger);

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

            case 4:
                $updateCount = MeasurementsUtil::generateAnimalIdAndDateValues($this->conn, false);
                if($updateCount > 0) {
                    $output->writeln($updateCount.' animalIdAndDate values in measurement table updated');
                } else {
                    $output->writeln('No animalIdAndDate values in measurement table needed to be updated');
                }
                break;

            case 5:
                $littersDeleted = LitterUtil::deleteDuplicateLittersWithoutBornAlive($this->conn);
                $output->writeln($littersDeleted . ' litters deleted');
                $output->writeln('Done!');
                break;

            case 6:
                $ascendantValidator->run();
                $output->writeln('Done!');
                break;

            case 7:
                $ascendantValidator->printOverview();
                $output->writeln('Done!');
                break;

            case 8:
                DatabaseDataFixer::recursivelyFillMissingBreedCodesHavingBothParentBreedCodes($this->conn, $this->cmdUtil);
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }
    }
    

}
