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
    const DEFAULT_BATCH_SIZE = 500;
    const DEFAULT_START_ANIMAL_ID = 0;
    const DEFAULT_MAX_DAYS_DIFFERENCE_BETWEEN_PARENT_AND_CHILD = 350;
    const DELETE_DURING_SYNC = false;

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
    /** @var int */
    private $maxDaysDifferenceBetweenParentAndChild;

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
        $this->pedigreeSearchArray = [];
        $this->ascendantsSearchArray = [];
        $this->incorrectPedigrees = [];
        $this->toProcess = [];

        $this->deleteCount = 0;
        $this->updateCount = 0;
        $this->insertCount = 0;
        $this->lastSyncedAnimalId = 0;
        $this->startAnimalId = self::DEFAULT_START_ANIMAL_ID;
    }


    public function printOverview()
    {
        /** @var ErrorLogAnimalPedigree $errorLogAnimalPedigree */
        foreach ($this->errorLogAnimalPedigreeRepository->findAll() as $errorLogAnimalPedigree)
        {
            $types = $errorLogAnimalPedigree->getParentTypesAsArray();

            foreach ($errorLogAnimalPedigree->getParentIdsAsArray() as $key => $parentId) {
                $sql = "SELECT
                          CONCAT(a.uln_country_code,a.uln_number) as uln, CONCAT(a.pedigree_country_code,a.pedigree_number) as stn,
                          DATE(a.date_of_birth) as date_of_birth, a.name,
                          CONCAT(mom.uln_country_code,mom.uln_number) as uln_mother, CONCAT(mom.pedigree_country_code,mom.pedigree_number) as stn_mother,
                          CONCAT(dad.uln_country_code,dad.uln_number) as uln_father, CONCAT(dad.pedigree_country_code,dad.pedigree_number) as stn_father
                        FROM animal a
                          LEFT JOIN animal mom ON mom.id = a.parent_mother_id
                          LEFT JOIN animal dad ON dad.id = a.parent_father_id
                        WHERE a.id = $parentId";
                $result = $this->conn->query($sql)->fetch();

                $this->writeln(['uln' => $result['uln'],
                    'stn' => $result['stn'],
                    'dateOfBirth' => $result['date_of_birth'],
                    'vsmId' => $result['name']]);

                if($key < count($types) - 1) {
                    $this->writeln(' ');
                    $this->writeln(' --- ' . ArrayUtil::get($key + 1, $types) . ' --- ');
                }
            }
            $this->writeln('=============');

        }
    }


    public function run()
    {
        $this->initializePrivateValues();
        $this->chooseStartValues();
        $this->createSearchArray();
        $this->findDirectParents();
        $this->findAscendants();
        $this->syncIncorrectPedigreesWithDatabase(true);
    }


    private function chooseStartValues()
    {
        $this->maxDaysDifferenceBetweenParentAndChild = $this->cmdUtil->generateQuestion('maxDaysDifferenceParentAndChild (default = '.self::DEFAULT_MAX_DAYS_DIFFERENCE_BETWEEN_PARENT_AND_CHILD.')', self::DEFAULT_MAX_DAYS_DIFFERENCE_BETWEEN_PARENT_AND_CHILD, $isCleanupString = true);
        $this->startAnimalId = $this->cmdUtil->generateQuestion('Start animalId (default = '.self::DEFAULT_START_ANIMAL_ID.')', self::DEFAULT_START_ANIMAL_ID, $isCleanupString = true);
        $this->batchSize = $this->cmdUtil->generateQuestion('BatchSize (default = '.self::DEFAULT_BATCH_SIZE.')', self::DEFAULT_BATCH_SIZE, $isCleanupString = true);

        $this->writeln('maxDaysDifferenceBetweenParentAndChild: '.$this->maxDaysDifferenceBetweenParentAndChild.'  startAnimalId: '.$this->startAnimalId.'  batchSize: '.$this->batchSize);
    }


    private function createSearchArray()
    {
        $sql = "SELECT
                  a.id as animal_id, a.parent_mother_id, a.parent_father_id, a.type,
                  dad.id as parent_id, dad.parent_mother_id as parent_parent_mother_id, dad.parent_father_id as parent_parent_father_id, dad.type as parent_type
                FROM animal a
                  INNER JOIN animal dad ON dad.id = a.parent_father_id
                WHERE DATE_PART('days', a.date_of_birth - dad.date_of_birth) < ".$this->maxDaysDifferenceBetweenParentAndChild." AND a.type <> 'Neuter'
                ORDER BY a.id ASC";
        $fatherResults = $this->conn->query($sql)->fetchAll();

        $sql = "SELECT
                  a.id as animal_id, a.parent_mother_id, a.parent_father_id, a.type,
                  mom.id as parent_id, mom.parent_mother_id as parent_parent_mother_id, mom.parent_father_id as parent_parent_father_id, mom.type as parent_type
                FROM animal a
                  INNER JOIN animal mom ON mom.id = a.parent_mother_id
                WHERE DATE_PART('days', a.date_of_birth - mom.date_of_birth) < ".$this->maxDaysDifferenceBetweenParentAndChild." AND a.type <> 'Neuter'
                ORDER BY a.id ASC";
        $motherResults = $this->conn->query($sql)->fetchAll();

        foreach ([$fatherResults, $motherResults] as $parentResults) {
            foreach ($parentResults as $parentResult) {
                $animalId = $parentResult['animal_id'];
                $fatherId = $parentResult['parent_father_id'];
                $motherId = $parentResult['parent_mother_id'];
                $type = $parentResult['type'];

                $pedigreeRecord = [
                    'animal_id' => $animalId,
                    'parent_father_id' => $fatherId,
                    'parent_mother_id' => $motherId,
                    'type' => $type,
                ];

                $this->pedigreeSearchArray[$animalId] = $pedigreeRecord;

                $parentId = $parentResult['parent_id'];
                $parentFatherId = $parentResult['parent_parent_mother_id'];
                $parentMotherId = $parentResult['parent_parent_mother_id'];
                $parentType = $parentResult['parent_type'];

                $parentPedigreeRecord = [
                    'animal_id' => $parentId,
                    'parent_father_id' => $parentFatherId,
                    'parent_mother_id' => $parentMotherId,
                    'type' => $parentType,
                ];

                $this->pedigreeSearchArray[$parentId] = $parentPedigreeRecord;
            }
        }

        ksort($this->pedigreeSearchArray, SORT_NUMERIC);

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
            $this->syncIncorrectPedigreesWithDatabase();
            $this->lastSyncedAnimalId = $lastCheckedAnimalId;
            $message = $this->getProgressBarMessage(); //update message
            gc_collect_cycles();
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

        if($checkForDeletes && self::DELETE_DURING_SYNC) {
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
     * @param string|array $input
     * @param int $indentLevel
     */
    private function writeln($input, $indentLevel = 0)
    {
        if(!is_array($input)) {
            $this->writelnString($input);

        } else {
            foreach ($input as $key => $value) {
                $this->indent($indentLevel);

                if(is_array($value)) {
                    $this->writeln($key.' : {', $indentLevel);
                    $this->indent($indentLevel);
                    $this->writeln($value, $indentLevel+1);
                    $this->indent($indentLevel);
                    $this->writeln('   }', $indentLevel);
                } else {
                    $this->writeln($key.' : '.$value, $indentLevel);
                }
            }
        }
    }


    /**
     * @param int $indentCount
     * @param string $indentType
     */
    private function indent($indentCount = 1, $indentType = '      ')
    {
        $this->writelnString(str_repeat($indentType, $indentCount));
    }


    /**
     * @param $string
     */
    private function writelnString($string)
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