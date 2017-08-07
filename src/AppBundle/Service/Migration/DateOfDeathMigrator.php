<?php


namespace AppBundle\Service\Migration;


use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\AnimalResidenceRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class DateOfDeathMigrator
 *
 * NOTE!!! (2017-01-02) Currently only the dateOfdeath and isAlive status is migrated/updated on existing animals!
 *
 * This is because matching the correct arrival and depart dates / start- and endDates for the animalResidences
 * needs a lot of work which will probably not be worth it.
 * Furthermore there is no guarantee that the generated animalResidences will actually be correct.
 * There is a high risk that the data will become corrupted.
 */
class DateOfDeathMigrator extends Migrator2017JunServiceBase implements IMigratorService
{
    const DEPART = 'Afvoer';
    const ARRIVAL = 'Aanvoer';
    const DEATH = 'Dood';

    /** @var AnimalResidenceRepository */
    private $animalResidenceRepository;

    /**
     * DateOfDeathMigrator constructor.
     * @param ObjectManager $em
     * @param string $rootDir
     */
    public function __construct(ObjectManager $em, $rootDir)
    {
        parent::__construct($em, $rootDir, self::BATCH_SIZE, self::IMPORT_SUB_FOLDER);
        $this->animalResidenceRepository = $em->getRepository(AnimalResidence::class);
    }

    /**
     * @param CommandUtil $cmdUtil
     */
    public function run(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        $this->writeln(['',
            'Generating searchArrays...']);

        $this->resetPrimaryVsmIdsBySecondaryVsmId();

        $this->animalIdsByVsmId = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();

        $sql = 'SELECT id FROM animal WHERE date_of_death ISNULL OR is_alive = TRUE';
        $results = $this->conn->query($sql)->fetchAll();
        $animalIdsOfAliveAnimals = SqlUtil::getSingleValueGroupedSqlResults('id', $results, true);

        $this->data = $this->parseCSV(self::RESIDENCE);

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
            //$secondaryUbn = $record[4];

            //First get the correct vsmId before getting the animalId
            if(array_key_exists($vsmId, $this->primaryVsmIdsBySecondaryVsmId)) {
                $vsmId = $this->primaryVsmIdsBySecondaryVsmId[$vsmId];
            }

            $animalId = null;
            if(array_key_exists($vsmId, $this->animalIdsByVsmId)) {
                $animalId = intval($this->animalIdsByVsmId[$vsmId]);
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
                    $this->writeln('Write code to process the following mutationType: '.$mutationType);
                    die;
            }

            $this->cmdUtil->advanceProgressBar(1,
                'Deaths new|skipped|incomplete|notDeath: '.$newDeaths.'|'.$skippedDeaths.'|'.$incompleteRecords.'|'.$notDeath);
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }
}