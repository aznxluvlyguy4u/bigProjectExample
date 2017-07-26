<?php

namespace AppBundle\Command;

use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
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
    
    protected function configure()
    {
        $this
            ->setName('nsfo:fix:duplicate:animals')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManagerInterface|ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $duplicateAnimalsFixer = $this->getContainer()->get('app.datafix.animals.duplicate');
        $this->em = $em;
        $this->conn = $em->getConnection();
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));
        $output->writeln(DoctrineUtil::getDatabaseHostAndNameString($em));

        $option = $this->cmdUtil->generateMultiLineQuestion([
            ' ', "\n",
            'Choose option: ', "\n",
            '1: Fix duplicate animals, near identical including duplicate vsmId', "\n",
            '2: Fix duplicate animals, synced I&R vs migrated animals', "\n",
            '3: Merge two animals by primaryKeys', "\n",
            '4: Merge two animals where one is missing leading zeroes', "\n",
            '5: Fix duplicate animals due to tagReplace error', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);
        
        switch ($option) {
            case 1: $duplicateAnimalsFixer->fixDuplicateAnimalsGroupedOnUlnVsmIdDateOfBirth($this->cmdUtil); break;
            case 2: $duplicateAnimalsFixer->fixDuplicateAnimalsSyncedAndImportedPairs($this->cmdUtil); break;
            case 3: $duplicateAnimalsFixer->mergeAnimalPairs($this->cmdUtil); break;
            case 4: $duplicateAnimalsFixer->mergeImportedAnimalsMissingLeadingZeroes($this->cmdUtil); break;
            case 5: $duplicateAnimalsFixer->fixDuplicateDueToTagReplaceError($this->cmdUtil); break;
            default: $output->writeln('ABORTED'); return;
        }

        gc_collect_cycles();
        $output->writeln('DONE');
    }

}