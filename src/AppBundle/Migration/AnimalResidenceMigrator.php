<?php


namespace AppBundle\Migration;


use AppBundle\Util\CommandUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class AnimalResidenceMigrator extends MigratorBase
{
    /** @var array */
    private $animalIdsByVsmIds;

    /**
     * AnimalResidenceMigrator constructor.
     * @param CommandUtil $cmdUtil
     * @param ObjectManager $em
     * @param OutputInterface $output
     * @param array $data
     */
    public function __construct(CommandUtil $cmdUtil, ObjectManager $em, OutputInterface $output, array $data)
    {
        parent::__construct($cmdUtil, $em, $output, $data);
    }

    public function migrate()
    {
        $this->cmdUtil->setStartTimeAndPrintIt(count($this->data), 1);
        $this->resetPrimaryVsmIdsBySecondaryVsmId();

        /* TODO
         * SearchArrays
         * - LocationIds by ubn, prioritize active locations
         * - animalResidence, current
         */


        $this->animalIdsByVsmIds = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();

        $newCount = 0;
        foreach ($this->data as $record) {

            $vsmId = $record[0];
            $dateString = TimeUtil::fillDateStringWithLeadingZeroes($record[1]);
            $mutationType = $record[2];
            $primaryUbn = $record[3];
            $secondaryUbn = $record[4];


            if(array_key_exists($vsmId, $this->primaryVsmIdsBySecondaryVsmId)) {
                $vsmId = $this->primaryVsmIdsBySecondaryVsmId[$vsmId];
            }

            //TODO check if record has already been processed, by checking the animalResidence data

            //Process by MutationType

            switch ($mutationType) {
                case 'Afvoer':
                    //TODO create/edit animalResidence record
                    break;

                case 'Aanvoer':
                    //TODO create/edit animalResidence record
                    break;

                case 'Dood':
                    //TODO create/edit animalResidence record
                    //TODO setDateOfDeath and isAlive = false
                    break;

                case '':
                    //Do not process records with missing mutationTypes
                    break;

                default;
                    $this->output->writeln('Write code to process the following mutationType: '.$mutationType);
                    die;
            }

            $this->cmdUtil->advanceProgressBar(1);
        }
        $this->cmdUtil->setProgressBarMessage($newCount.' new records persisted');
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


}