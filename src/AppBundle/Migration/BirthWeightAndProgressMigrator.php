<?php


namespace AppBundle\Migration;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\AnimalResidenceRepository;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\Weight;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Translation;
use AppBundle\Util\Validator;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class BirthWeightAndProgressMigrator
 *
 * @ORM\Entity(repositoryClass="AppBundle\Migration")
 * @package AppBundle\Migration
 */
class BirthWeightAndProgressMigrator extends MigratorBase
{
    const DEFAULT_ANIMAL_ID = 1;

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
        $this->output->writeln(['=== Importing birth weight/progress/tailLength ===',
                                'Generating searchArrays...',
                                'Note! If a birth weight/tailLength already exists, it will be skipped']);

        $this->resetPrimaryVsmIdsBySecondaryVsmId();

        $this->animalIdsByVsmIds = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();

        $sql = "SELECT w.animal_id
                FROM weight w
                  INNER JOIN measurement m ON m.id = w.id
                  INNER JOIN animal a ON a.id = w.animal_id
                WHERE w.is_birth_weight = FALSE OR DATE(a.date_of_birth) = DATE(m.measurement_date)
                GROUP BY animal_id HAVING COUNT(*) > 0 ";
        $results = $this->conn->query($sql)->fetchAll();
        $birthWeightsAnimalIds = SqlUtil::getSingleValueGroupedSqlResults('animal_id', $results, true, true);
        
        $sql = "SELECT birth_progress, id FROM animal WHERE birth_progress NOTNULL ";
        $results = $this->conn->query($sql)->fetchAll();
        $birthProgressByAnimalId = SqlUtil::groupSqlResultsOfKey1ByKey2('birth_progress', 'id', $results);

        $sql = "SELECT animal_id 
                FROM tail_length t 
                INNER JOIN measurement m ON t.id = m.id
                INNER JOIN animal a ON a.id = t.animal_id
                WHERE DATE(a.date_of_birth) = DATE(m.measurement_date) 
                GROUP BY animal_id HAVING COUNT(*) > 0";
        $results = $this->conn->query($sql)->fetchAll();
        $birthTailLengthAnimalIds = SqlUtil::getSingleValueGroupedSqlResults('animal_id', $results, true, true);


        $this->cmdUtil->setStartTimeAndPrintIt(count($this->data), 1);
        
        $animalsMissingCount = 0;
        $newBirthWeightsCount = 0;
        $newProgressCount = 0;
        $newTailLengthCount = 0;
        
        $birthProgressUpdateString = '';

        $entityBatchSize = 100;
        $sqlBatchSize = 1000;
        $entityCounter = 0;
        $sqlCounter = 0;
        $logDate = new \DateTime();
        foreach ($this->data as $record) {

            $vsmId = $record[0];

            $animalId = $this->getAnimalIdFromVsmId($vsmId);

            //skip if no animal is found
            if($animalId == null || $animalId == '') {
                $animalsMissingCount++;
                $this->cmdUtil->advanceProgressBar(1,
                    'Animals missing: '.$animalsMissingCount.'  New birth weight|progress|tailLength: '.$newBirthWeightsCount.'|'.$newProgressCount.'|'.$newTailLengthCount);
                continue;
            }

            $weightValue = $record[1];
            $tailLengthValue = $record[2];
            $birthProgress = Translation::getEnglish(strtr($record[3],['Zonder hulp'=>'Zonder Hulp']));


            $insertNewTailLength = false;
            if(Validator::isStringAFloat($tailLengthValue)) {
                $tailLengthValue = StringUtil::replaceCommasWithDots($tailLengthValue);
                if(!array_key_exists($animalId, $birthTailLengthAnimalIds)) {
                    $insertNewTailLength = true;
                }
            }

            $insertNewBirthWeight = false;
            if(Validator::isStringAFloat($weightValue)) {
                $weightValue = StringUtil::replaceCommasWithDots($weightValue);
                if(!array_key_exists($animalId, $birthWeightsAnimalIds)) {
                    $insertNewBirthWeight = true;
                }
            }



            $animal = null;
            $animalIdAndDateString = null;
            if($insertNewTailLength || $insertNewBirthWeight) {
                /** @var Animal $animal */
                $animal = $this->animalRepository->find($animalId);
                $animalIdAndDateString = StringUtil::getAnimalIdAndDateString($animal, $animal->getDateOfBirth());
            }

            //Create new tailLength
            if($insertNewTailLength) {
                if($animal->getDateOfBirth()) {
                    $tailLength = new TailLength();
                    $tailLength->setAnimal($animal);
                    $tailLength->setLogDate($logDate);
                    $tailLength->setMeasurementDate($animal->getDateOfBirth());
                    $tailLength->setAnimalIdAndDate($animalIdAndDateString);
                    $tailLength->setLength($tailLengthValue);
                    $this->em->persist($tailLength);

                    $birthTailLengthAnimalIds[$animalId] = $animalId;
                    $newTailLengthCount++;
                    $entityCounter++;
                }
            }

            //Create new birthWeight
            if($insertNewBirthWeight) {
                $weight = new Weight();
                $weight->setAnimal($animal);
                $weight->setLogDate($logDate);
                $weight->setMeasurementDate($animal->getDateOfBirth());
                $weight->setAnimalIdAndDate($animalIdAndDateString);
                $weight->setWeight($weightValue);
                $weight->setIsRevoked(false);
                $weight->setIsBirthWeight(true);
                $this->em->persist($weight);
                $birthWeightsAnimalIds[$animalId] = $animalId;
                $newBirthWeightsCount++;
                $entityCounter++;
            }

            //Update animal with birthProgress value
            if($birthProgress != '' && $birthProgress != null) {
                if(!array_key_exists($animalId, $birthProgressByAnimalId)) {
                    $birthProgressUpdateString = $birthProgressUpdateString."(".$animalId.",'".$birthProgress."'),";
                    $birthProgressByAnimalId[$animalId] = $birthProgress;
                    $newProgressCount++;
                    $sqlCounter++;
                }
            }


            if($entityCounter%$entityBatchSize == 0) { $this->em->flush(); }
            if($sqlCounter%$sqlBatchSize == 0) {
                $this->batchUpdate($birthProgressUpdateString);
                $birthProgressUpdateString = '';
            }

            $this->cmdUtil->advanceProgressBar(1,
                'Animals missing: '.$animalsMissingCount.'  New birth weight|progress|tailLength: '.$newBirthWeightsCount.'|'.$newProgressCount.'|'.$newTailLengthCount);
        }
        $this->em->flush();
        $this->batchUpdate($birthProgressUpdateString);

        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    /**
     * @param string $birthProgressUpdateString
     * @throws \Doctrine\DBAL\DBALException
     */
    private function batchUpdate($birthProgressUpdateString)
    {
        if($birthProgressUpdateString != '') {
            $birthProgressUpdateString = rtrim($birthProgressUpdateString, ',');
            $sql = "UPDATE animal SET birth_progress = v.birth_progress
                    FROM (VALUES ".$birthProgressUpdateString."
                    ) AS v(animal_id, birth_progress) WHERE animal.id = v.animal_id";
            $this->conn->exec($sql);
        }
    }


    /**
     * @param string $vsmId
     * @return int|null
     */
    private function getAnimalIdFromVsmId($vsmId)
    {
        //First get the correct vsmId before getting the animalId
        if(array_key_exists($vsmId, $this->primaryVsmIdsBySecondaryVsmId)) {
            $vsmId = $this->primaryVsmIdsBySecondaryVsmId[$vsmId];
        }

        $animalId = null;
        if(array_key_exists($vsmId, $this->animalIdsByVsmIds)) {
            $animalId = intval($this->animalIdsByVsmIds[$vsmId]);
        }
        return $animalId;
    }
}