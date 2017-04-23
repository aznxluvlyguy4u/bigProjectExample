<?php

namespace AppBundle\Command;

use AppBundle\Migration\DuplicateAnimalsFixer;
use AppBundle\Migration\DuplicateAnimalsFixerLegacy;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoFixDuplicateAnimalsCommand extends ContainerAwareCommand
{
    const TITLE = 'Fix Duplicate Animals: Due to Animal Import after AnimalSync around July-Aug 2016 and TagReplace error in Sep2016';
    const DEFAULT_OPTION = 0;
    const ACTIVATE_LEGACY_COMMAND = false;
    const BLOCKED_DATABASE_NAME_PART = 'prod';

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
            ->setName('nsfo:fix:duplicate:animals')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->output = $output;
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));
        $output->writeln(DoctrineUtil::getDatabaseHostAndNameString($em));
        $databaseName = $this->conn->getDatabase();

        $option = $this->cmdUtil->generateMultiLineQuestion([
            ' ', "\n",
            'Choose option: ', "\n",
            '1: Fix duplicate animals, near identical including duplicate vsmId', "\n",
            '2: Fix duplicate animals, synced I&R vs migrated animals', "\n",
            '3: Fix duplicate animals (legacy)', "\n",
            '4: Merge two animals by primaryKeys', "\n",
            '5: Merge two animals where one is missing leading zeroes', "\n",
            '6: Fix duplicate animals due to tagReplace error', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);

        
        switch ($option) {
            
            case 1:
                $duplicateAnimalsFixer = new DuplicateAnimalsFixer($em, $output, $this->cmdUtil);
                $duplicateAnimalsFixer->fixDuplicateAnimalsWithIdenticalVsmIds();
                $output->writeln('DONE');
                break;
            
            case 2:
                //TODO
                $duplicateAnimalsFixer = new DuplicateAnimalsFixer($em, $output, $this->cmdUtil);
                $duplicateAnimalsFixer->fixDuplicateSyncedVsMigratedAnimals();
                $output->writeln('DONE');
                break;
            
            case 3:
                if(self::ACTIVATE_LEGACY_COMMAND) {
                    $duplicateAnimalsFixerLegacy = new DuplicateAnimalsFixerLegacy($em, $output, $this->cmdUtil);
                    $duplicateAnimalsFixerLegacy->fixDuplicateAnimals();
                    $output->writeln('DONE');
                } else {
                    $output->writeln('This command is deactivated');
                }
                break;

            case 4:
                $duplicateAnimalsFixer = new DuplicateAnimalsFixer($em, $output, $this->cmdUtil);
                $duplicateAnimalsFixer->mergeAnimalPairs();
                $output->writeln('DONE');
                break;

            case 5:
                $duplicateAnimalsFixer = new DuplicateAnimalsFixer($em, $output, $this->cmdUtil);
                $duplicateAnimalsFixer->mergeImportedAnimalsMissingLeadingZeroes();
                $output->writeln('DONE');
                break;

            case 6:
                $duplicateAnimalsFixer = new DuplicateAnimalsFixer($em, $output, $this->cmdUtil);
                $duplicateAnimalsFixer->fixDuplicateDueToTagReplaceError();
                $output->writeln('DONE');
                break;
            
            default:
                $output->writeln('ABORTED');
                break;
        }
        $duplicateAnimalsFixer = null;
        gc_collect_cycles();
    }




    


}
