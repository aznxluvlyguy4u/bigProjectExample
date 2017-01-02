<?php


namespace AppBundle\Migration;


use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\AnimalResidenceRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class AnimalResidenceMigrator
 *
 * NOTE!!! (2017-01-02) Currently only the dateOfdeath and isAlive status is migrated/updated on existing animals!
 *
 * This is because matching the correct arrival and depart dates / start- and endDates for the animalResidences
 * needs a lot of work which will probably not be worth it.
 * Furthermore there is no guarantee that the generated animalResidences will actually be correct.
 * There is a high risk that the data will become corrupted.
 *
 * @ORM\Entity(repositoryClass="AppBundle\Migration")
 * @package AppBundle\Migration
 */
class AnimalResidenceMigrator extends MigratorBase
{
    const DEFAULT_ANIMAL_ID = 1;
    
    const ANIMAL_ID = 'animal_id';
    const PRIMARY_LOCATION_ID = 'primary_location_id';
    const SECONDARY_LOCATION_ID = 'secondary_location_id';
    const DATE_STRING = 'date_string';
    const MUTATION_TYPE = 'mutation_type';

    const DEPART = 'Afvoer';
    const ARRIVAL = 'Aanvoer';
    const DEATH = 'Dood';

    /** @var array */
    private $animalIdsByVsmIds;

    /** @var AnimalResidenceRepository */
    private $animalResidenceRepository;

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
        $this->animalResidenceRepository = $em->getRepository(AnimalResidence::class);
    }

    public function migrate()
    {
        $this->output->writeln(['',
                                'Generating searchArrays...']);

        $this->resetPrimaryVsmIdsBySecondaryVsmId();

        $this->animalIdsByVsmIds = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();

        $sql = 'SELECT id FROM animal WHERE date_of_death ISNULL OR is_alive = TRUE';
        $results = $this->conn->query($sql)->fetchAll();

        $animalIdsOfAliveAnimals = [];
        foreach ($results as $result) {
            $animalIdsOfAliveAnimals[$result['id']] = $result['id'];
        }


        $this->cmdUtil->setStartTimeAndPrintIt(count($this->data), 1);

        $incompleteRecords = 0;
        $newDeaths = 0;
        $skippedDeaths = 0;
        $notDeath = 0;

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
                $animalId = intval($this->animalIdsByVsmIds[$vsmId]);
            }

            //NullCheck
            if($animalId == null || $primaryUbn == '' || $mutationType == '' || $dateString == '' || $dateString == null) {
                //There are no death records without primaryUbn by the way, so keep the check in case animalResidence migration is added
                $incompleteRecords++;
                $this->cmdUtil->advanceProgressBar(1,
                    'Deaths new|skipped|incomplete|notDeath: '.$newDeaths.'|'.$skippedDeaths.'|'.$incompleteRecords.'|'.$notDeath);
                continue;
            }


            switch ($mutationType) {

                case self::DEATH:
                    if(array_key_exists($animalId, $animalIdsOfAliveAnimals)) {
                        $sql = "UPDATE animal SET is_alive = FALSE, date_of_death = '".$dateString."' WHERE id = ".$animalId;
                        $this->conn->exec($sql);
                        $newDeaths++;
                        unset($animalIdsOfAliveAnimals[$animalId]);
                    } else {
                        $skippedDeaths++;
                    }
                    break;

                case self::ARRIVAL: $notDeath++; break;
                case self::DEPART:  $notDeath++; break;
                case '':            $notDeath++; break; //Do not process records with missing mutationTypes
                default;
                    $this->output->writeln('Write code to process the following mutationType: '.$mutationType);
                    die;
            }

            $this->cmdUtil->advanceProgressBar(1,
                'Deaths new|skipped|incomplete|notDeath: '.$newDeaths.'|'.$skippedDeaths.'|'.$incompleteRecords.'|'.$notDeath);
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


}