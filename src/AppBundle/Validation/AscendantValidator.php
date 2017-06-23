<?php


namespace AppBundle\Validation;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\ErrorLogAnimalPedigree;
use AppBundle\Entity\ErrorLogAnimalPedigreeRepository;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Monolog\Logger;

/**
 * Find animals with themselves being their own ascendant.
 *
 * Class AscendantValidator
 * @package AppBundle\Validation
 */
class AscendantValidator
{
    const DEFAULT_BATCH_SIZE = 50000;
    const DEFAULT_START_ANIMAL_ID = 0;

    /** @var ObjectManager */
    private $em;
    /** @var Connection */
    private $conn;
    /** @var CommandUtil */
    private $cmdUtil;
    /** @var Logger */
    private $logger;

    /** @var ErrorLogAnimalPedigreeRepository */
    private $errorLogAnimalPedigreeRepository;

    /** @var array */
    private $pedigreeSearchArray;
    /** @var array */
    private $femalePedigreeSearchArray;
    /** @var array */
    private $malePedigreeSearchArray;
    /** @var array */
    private $ascendantsSearchArray;
    /** @var array */
    private $incorrectPedigrees;
    /** @var array */
    private $toProcess;

    /** @var int */
    private $deleteCount;
    /** @var int */
    private $updateCount;
    /** @var int */
    private $insertCount;
    /** @var int */
    private $startAnimalId;
    /** @var int */
    private $lastSyncedAnimalId;
    /** @var int */
    private $batchSize;

    /**
     * AscendantValidator constructor.
     * @param ObjectManager $em
     * @param CommandUtil $cmdUtil
     * @param Logger $logger
     */
    public function __construct(ObjectManager $em, CommandUtil $cmdUtil, Logger $logger = null)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->logger = $logger;
        $this->cmdUtil = $cmdUtil;

        $this->errorLogAnimalPedigreeRepository = $this->em->getRepository(ErrorLogAnimalPedigree::class);
    }


    private function initializePrivateValues()
    {
        $this->writeln('Initialize empty private arrays ...');
        $this->malePedigreeSearchArray = [];
        $this->femalePedigreeSearchArray = [];
        $this->ascendantsSearchArray = [];
        $this->incorrectPedigrees = [];

        $this->deleteCount = 0;
        $this->updateCount = 0;
        $this->insertCount = 0;
        $this->lastSyncedAnimalId = 0;
        $this->startAnimalId = self::DEFAULT_START_ANIMAL_ID;
    }


    public function run()
    {
        $this->initializePrivateValues();
        $this->chooseStartValues();
        $this->findDirectParents();
        $this->findAscendants();
        $this->syncIncorrectPedigreesWithDatabase(true);
    }


    private function chooseStartValues()
    {
        $this->startAnimalId = $this->cmdUtil->generateQuestion('Start animalId (default = '.self::DEFAULT_START_ANIMAL_ID.')', self::DEFAULT_START_ANIMAL_ID, $isCleanupString = true);
        $this->batchSize = $this->cmdUtil->generateQuestion('BatchSize (default = '.self::DEFAULT_BATCH_SIZE.')', self::DEFAULT_BATCH_SIZE, $isCleanupString = true);

        $this->writeln('startAnimalId: '.$this->startAnimalId.'  batchSize: '.$this->batchSize);
    }


    private function findDirectParents()
    {
        $this->writeln('Find direct parents ... ');
        $sql = "SELECT a.id as animal_id, a.parent_mother_id, a.parent_father_id, type
                FROM animal a
                WHERE type <> 'Neuter'
                ORDER BY a.id ASC";
        $fullPedigreeData = $this->conn->query($sql)->fetchAll();

        $this->writeln('Creating pedigree search arrays ...');
        foreach ($fullPedigreeData as $pedigreeRecord)
        {
            $animalId = $pedigreeRecord['animal_id'];
            $fatherId = $pedigreeRecord['parent_father_id'];
            $motherId = $pedigreeRecord['parent_mother_id'];
            $type = $pedigreeRecord['type'];

            if($fatherId != null || $motherId != null) {
                $this->pedigreeSearchArray[$animalId] = $pedigreeRecord;
                if($type == AnimalObjectType::Ram) {
                    $this->malePedigreeSearchArray[$animalId] = $pedigreeRecord;
                } elseif($type == AnimalObjectType::Ewe) {
                    $this->femalePedigreeSearchArray[$animalId] = $pedigreeRecord;
                }
            }
        }
    }


    private function findAscendants()
    {
        $this->writeln('=== Check for animals being their own ascendants | ErrorLogAnimalPedigree sync  with database ===');

        //Prepare arrays
        $parentSearchArray = $this->pedigreeSearchArray;
        if($this->startAnimalId > 0) {
            $startPosition = ArrayUtil::keyPosition($this->startAnimalId, $parentSearchArray);
            $parentSearchArray = array_slice($parentSearchArray, $startPosition, null, true);
        }

        $currentErrorLogAnimalPedigreesInDatabase = $this->errorLogAnimalPedigreeRepository->findAllAsSearchArray();
        foreach ($currentErrorLogAnimalPedigreesInDatabase as $animalId => $values)
        {
            if($animalId <= $this->startAnimalId) {
                $this->incorrectPedigrees[$animalId] = $values;
            }
        }


        $totalCount = count($parentSearchArray);
        $message = $this->getProgressBarMessage();
        $counter = 0;
        $lastCheckedAnimalId = $this->startAnimalId;
        $checkForDeletes = true;

        $this->cmdUtil->setStartTimeAndPrintIt($totalCount, 1);

        for($i = 0;  $i < $totalCount; $i += $this->batchSize) {

            $slicedParentSearchArray = array_slice($parentSearchArray, $i, $this->batchSize, true);

            foreach ($slicedParentSearchArray as $animalId => $parents)
            {
                $this->ascendantsSearchArray = [];

                $ascendants = [];
                $ascendants[] = $animalId;
                $animalIdByTypeChain = [];
                $animalIdByTypeChain[] = [
                    'animal_id' => $animalId,
                    'type' => 'child',
                ];

                $this->addParentsToAscendants($animalId, $parents, $ascendants, $animalIdByTypeChain);

                $counter++;
                $lastCheckedAnimalId = $animalId;
                $this->cmdUtil->advanceProgressBar(1, $message);
            }

            //At end of each batch
            $this->syncIncorrectPedigreesWithDatabase($checkForDeletes);
            $this->lastSyncedAnimalId = $lastCheckedAnimalId;
            $message = $this->getProgressBarMessage(); //update message
            gc_collect_cycles();
            $checkForDeletes = false;
        }

        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    private function getProgressBarMessage()
    {
        return 'Sync with db toProcess||delete|update|insert: '
            . count($this->toProcess).'||'.$this->deleteCount.'|'.$this->updateCount.'|'.$this->insertCount
            .'  lastSynced animalId: '.$this->lastSyncedAnimalId;
    }


    private function addParentsToAscendants($primaryAnimalId, $parents, $ascendants = [], $animalIdByTypeChain = [])
    {
        if($parents === null || $primaryAnimalId === null) { return; }

        $fatherId = $parents['parent_father_id'];
        $motherId = $parents['parent_mother_id'];

        if($fatherId == null && $motherId == null) {
            return;
        }

        $animalIdByTypeChainFather = $animalIdByTypeChain;
        $animalIdByTypeChainMother = $animalIdByTypeChain;
        $ascendantsFather = $ascendants;
        $ascendantsMother = $ascendants;
        $breakOutOfFatherLoop = false;
        $breakOutOfMotherLoop = false;


        $group = ArrayUtil::get($primaryAnimalId, $this->ascendantsSearchArray, []);

        if($fatherId) {

            $animalIdByTypeChainFather[] = [
                'animal_id' => $fatherId,
                'type' => "\"father\"",
            ];

            $ascendantsFather = $this->checkAscendants($fatherId, $ascendantsFather, $animalIdByTypeChainFather);
            if(!is_array($ascendantsFather)) { $breakOutOfFatherLoop = true; } //break out of infinite loop!

            $group[$fatherId] = $fatherId;
        }

        if($motherId) {

            $animalIdByTypeChainMother[] = [
                'animal_id' => $motherId,
                'type' => "\"mother\"",
            ];

            $ascendantsMother = $this->checkAscendants($motherId, $ascendantsMother, $animalIdByTypeChainMother);
            if(!is_array($ascendantsMother)) { $breakOutOfMotherLoop = true; } //break out of infinite loop!

            $group[$motherId] = $motherId;
        }


        $this->ascendantsSearchArray[$primaryAnimalId] = $group;

        if($fatherId && !$breakOutOfFatherLoop) {
            $parentsOfFather = ArrayUtil::get($fatherId, $this->malePedigreeSearchArray);
            if($parentsOfFather) {
                $this->addParentsToAscendants($primaryAnimalId, $parentsOfFather, $ascendantsFather, $animalIdByTypeChainFather);
            }
        }

        if($motherId && !$breakOutOfMotherLoop) {
            $parentsOfMother = ArrayUtil::get($motherId, $this->femalePedigreeSearchArray);
            if($parentsOfMother) {
                $this->addParentsToAscendants($primaryAnimalId, $parentsOfMother, $ascendantsMother, $animalIdByTypeChainMother);
            }
        }
    }


    /**
     * @param int $parentId
     * @param array $ascendants
     * @param array $animalIdByTypeChain
     * @return bool|array
     */
    private function checkAscendants($parentId, $ascendants, $animalIdByTypeChain)
    {
        if(key_exists($parentId, $ascendants)) {

            $chains = $this->parseChains($parentId, $animalIdByTypeChain);

            $this->incorrectPedigrees[$parentId] =
                [
                    JsonInputConstant::ANIMAL_ID => $parentId,
                    JsonInputConstant::PARENT_IDS => $chains['parent_ids'],
                    JsonInputConstant::PARENT_TYPES => $chains['parent_types'],
                ];

            $this->toProcess[$parentId] =
                [
                    JsonInputConstant::ANIMAL_ID => $parentId,
                    JsonInputConstant::PARENT_IDS => $chains['parent_ids'],
                    JsonInputConstant::PARENT_TYPES => $chains['parent_types'],
                ];

            return true;

        }

        $ascendants[$parentId] = $parentId;

        return $ascendants;
    }


    /**
     * @param $parentId
     * @param $animalIdByTypeChain
     * @return array
     */
    private function parseChains($parentId, $animalIdByTypeChain)
    {
        $animalIdChain = '';
        $parentTypes = '';
        $prefix = '';

        $foundStartOfChain = false;

        foreach ($animalIdByTypeChain as $values)
        {
            $animalId = $values['animal_id'];
            $type = $values['type'];

            if($animalId === $parentId) {
                $foundStartOfChain = true;
            }

            if($foundStartOfChain) {
                $animalIdChain = $animalIdChain . $prefix . $animalId;
                $parentTypes = $parentTypes . $prefix . $type;
                $prefix = ',';
            }
        }

        return [
            JsonInputConstant::PARENT_IDS => '['.$animalIdChain.']',
            JsonInputConstant::PARENT_TYPES => '['.$parentTypes.']',
        ];
    }


    private function syncIncorrectPedigreesWithDatabase($checkForDeletes = false)
    {
        $deleteByAnimalIds = [];
        $updateByAnimalIds = [];
        $insertByAnimalIds = [];

        $currentErrorLogAnimalPedigreesInDatabase = $this->errorLogAnimalPedigreeRepository->findAllAsSearchArray();

        if($checkForDeletes) {
            //Delete from database, if animalId is not in new set anymore
            foreach ($currentErrorLogAnimalPedigreesInDatabase as $animalIdInDatabase => $valuesInDatabase)
            {
                if(!key_exists($animalIdInDatabase, $this->incorrectPedigrees)) {
                    $deleteByAnimalIds[] = $animalIdInDatabase;
                }
            }

        }

        foreach ($this->toProcess as $animalId => $values)
        {
            if(key_exists($animalId,$currentErrorLogAnimalPedigreesInDatabase)) {

                $valuesInDatabase = $currentErrorLogAnimalPedigreesInDatabase[$animalId];

                //If animalId exists in database, but values are different update the values
                if($values[JsonInputConstant::PARENT_TYPES] != $valuesInDatabase[JsonInputConstant::PARENT_TYPES]
                || $values[JsonInputConstant::PARENT_IDS] != $valuesInDatabase[JsonInputConstant::PARENT_IDS]
                ) {
                    $updateByAnimalIds[$animalId] = $values;
                }

            } else {
                //Insert into database
                $insertByAnimalIds[$animalId] = $values;
            }
        }

        $this->deleteCount += $this->errorLogAnimalPedigreeRepository->removeByAnimalIds($deleteByAnimalIds);
        $this->updateCount += $this->errorLogAnimalPedigreeRepository->updateByAnimalIdArrays($updateByAnimalIds);
        $this->insertCount += $this->errorLogAnimalPedigreeRepository->insertByAnimalIdArrays($insertByAnimalIds);

        //Reset toProcess
        $this->toProcess = [];
    }



    /**
     * @param $string
     */
    private function writeln($string)
    {
        if ($this->logger) {
            $this->logger->notice($string);
        } elseif ($this->cmdUtil) {
            $this->cmdUtil->writeln($string);
        } else {
            echo $string;
        }
    }


}