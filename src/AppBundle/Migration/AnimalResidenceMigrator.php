<?php


namespace AppBundle\Migration;


use AppBundle\Entity\Location;
use AppBundle\Entity\LocationRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\Time;

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
         * - animalResidence, current
         */


        $this->animalIdsByVsmIds = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();

        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->em->getRepository(Location::class);
        $locationIdsByUbn = $locationRepository->getLocationIdsByUbn();

        $newCount = 0;
        $incompleteRecords = 0;
        $skippedRecords = 0;
        $newDeaths = 0;
        $skippedDeaths = 0;

        $logDateString = TimeUtil::getLogDateString();

        foreach ($this->data as $record) {

            $vsmId = $record[0];
            $dateString = TimeUtil::fillDateStringWithLeadingZeroes($record[1]);
            $mutationType = $record[2];
            $primaryUbn = $record[3];
            $secondaryUbn = $record[4];

            //First get the correct vsmId before getting the animalId
            if(array_key_exists($vsmId, $this->primaryVsmIdsBySecondaryVsmId)) {
                $vsmId = $this->primaryVsmIdsBySecondaryVsmId[$vsmId];
            }

            $animalId = null;
            if(array_key_exists($vsmId, $this->animalIdsByVsmIds)) {
                $animalId = $this->animalIdsByVsmIds[$vsmId];
            }

            //NullCheck
            if($animalId == null || $primaryUbn = '' || $mutationType = '') {
                //There are no death records without primaryUbn by the way
                $incompleteRecords++;
                $this->cmdUtil->advanceProgressBar(1,
                    'AnimalResidences incomplete|new|skipped: '.$incompleteRecords.'|'.$newCount.'|'.$skippedRecords.
                    '  Deaths new|skipped: '.$newDeaths.'|'.$skippedDeaths);
                continue;
            }


            $primaryLocationId = null;
            if($primaryUbn != '') {
                if(array_key_exists($primaryUbn, $locationIdsByUbn)) {
                    $primaryLocationId = $locationIdsByUbn[$primaryUbn];
                }
            }

            $secondaryLocationId = null;
            if($secondaryUbn != '') {
                if(array_key_exists($secondaryUbn, $locationIdsByUbn)) {
                    $secondaryLocationId = $locationIdsByUbn[$secondaryUbn];
                }
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

            $this->cmdUtil->advanceProgressBar(1,
                'AnimalResidences incomplete|new|skipped: '.$incompleteRecords.'|'.$newCount.'|'.$skippedRecords.
                '  Deaths new|skipped: '.$newDeaths.'|'.$skippedDeaths);
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


}