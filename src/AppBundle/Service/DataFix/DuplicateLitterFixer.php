<?php

namespace AppBundle\Service\DataFix;


use AppBundle\Entity\Litter;
use AppBundle\Entity\LitterRepository;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\LitterUtil;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class DuplicateLitterFixer
 */
class DuplicateLitterFixer extends DuplicateFixerBase
{
    const MATCH_LITTERS_WITH_MATES_BATCH_LIMIT = 10;

    /** @var LitterRepository $litterRepository */
    private $litterRepository;

    /** @var boolean */
    private $litterIdsForWhichToMatchMates;


    /**
     * DuplicateAnimalsFixer constructor.
     * @param ObjectManager $em
     */
    public function __construct(ObjectManager $em)
    {
        parent::__construct($em);
        $this->litterRepository = $this->em->getRepository(Litter::class);
    }


    /**
     * @param $primaryLitterId
     * @param $secondaryLitterId
     * @return bool
     */
    public function mergePairsById($primaryLitterId, $secondaryLitterId)
    {
        if (!is_int($primaryLitterId) || !is_int($secondaryLitterId) || intval($primaryLitterId) == intval($secondaryLitterId)) {
            return false;
        }

        $primaryLitter = $this->litterRepository->find($primaryLitterId);
        $secondaryLitter = $this->litterRepository->find($secondaryLitterId);

        if ($primaryLitter === null || $secondaryLitter === null) { return false; }

        $primaryLitterId = $primaryLitter->getId();
        $secondaryLitterId = $secondaryLitter->getId();

        if ($primaryLitterId === null || $secondaryLitterId === null) { return false; }

        /* 2. merge values */
        $isLitterIdMergeSuccessFul = $this->mergeLitterIdValuesInTables($primaryLitterId, $secondaryLitterId);
        $isLitterValueMergeSuccessFul = $this->mergeEmptyLitterValues($primaryLitter, $secondaryLitter);

        /* 3 Remove unnecessary duplicate */
        if($isLitterIdMergeSuccessFul && $isLitterValueMergeSuccessFul) {
            $this->em->remove($secondaryLitter);
            $this->em->flush();
            return true;
        }
        return false;
    }


    /***
     * @param $primaryLitterId
     * @param $secondaryLitterId
     * @return bool
     */
    private function mergeLitterIdValuesInTables($primaryLitterId, $secondaryLitterId)
    {
        if((!is_int($primaryLitterId) && !ctype_digit($primaryLitterId)) ||
            (!is_int($secondaryLitterId) && !ctype_digit($secondaryLitterId))) { return false; }

        $tableNamesByVariableType = [
            [ self::TABLE_NAME => 'animal',         self::VARIABLE_TYPE => 'litter_id' ],
            [ self::TABLE_NAME => 'stillborn',      self::VARIABLE_TYPE => 'litter_id' ],
            [ self::TABLE_NAME => 'declare_birth',  self::VARIABLE_TYPE => 'litter_id' ],
        ];

        $mergeResults = $this->mergeColumnValuesInTables($primaryLitterId, $secondaryLitterId, $tableNamesByVariableType);
        return $mergeResults[self::IS_MERGE_SUCCESSFUL];
    }


    /**
     * @param Litter $primaryLitter
     * @param Litter $secondaryLitter
     * @return bool
     */
    private function mergeEmptyLitterValues(Litter $primaryLitter, Litter $secondaryLitter)
    {
        $areAnyValuesUpdated = false;
        $areAnySecondaryValuesUpdated = false;

        if ($primaryLitter->getActionBy() === null && $secondaryLitter->getActionBy() !== null) {
           $primaryLitter->setActionBy($secondaryLitter->getActionBy()); $areAnyValuesUpdated = true;
        }

        //Do not merge a secondaryLitter Revoke status nor revoke values

        if ($primaryLitter->getRelationNumberKeeper() === null && $secondaryLitter->getRelationNumberKeeper() !== null) {
            $primaryLitter->setRelationNumberKeeper($secondaryLitter->getRelationNumberKeeper()); $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getUbn() === null && $secondaryLitter->getUbn() !== null) {
            $primaryLitter->setUbn($secondaryLitter->getUbn()); $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getAnimalFather() === null && $secondaryLitter->getAnimalFather() !== null) {
            $primaryLitter->setAnimalFather($secondaryLitter->getAnimalFather()); $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getAnimalMother() === null && $secondaryLitter->getAnimalMother() !== null) {
            $primaryLitter->setAnimalMother($secondaryLitter->getAnimalMother()); $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getMate() === null && $secondaryLitter->getMate() !== null) {
            //Mates must be set after deleting the secondary litters, so the mate cannot be set on the primary litter here.
            $this->litterIdsForWhichToMatchMates[] = $primaryLitter->getId();
            $secondaryLitter->setMate(null);
            $areAnySecondaryValuesUpdated = true;
        }

        if ($primaryLitter->getLitterOrdinal() === null && $secondaryLitter->getLitterOrdinal() !== null) {
            $primaryLitter->setLitterOrdinal($secondaryLitter->getLitterOrdinal()); $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getSuckleCount() === null && $secondaryLitter->getSuckleCount() !== null) {
            $primaryLitter->setSuckleCount($secondaryLitter->getSuckleCount()); $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getSuckleCountUpdateDate() === null && $secondaryLitter->getSuckleCountUpdateDate() !== null) {
            $primaryLitter->setSuckleCountUpdateDate($secondaryLitter->getSuckleCountUpdateDate()); $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getGestationPeriod() === null && $secondaryLitter->getGestationPeriod() !== null) {
            $primaryLitter->setGestationPeriod($secondaryLitter->getGestationPeriod()); $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getBirthInterval() === null && $secondaryLitter->getBirthInterval() !== null) {
            $primaryLitter->setBirthInterval($secondaryLitter->getBirthInterval()); $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getGestationPeriod() === null && $secondaryLitter->getGestationPeriod() !== null) {
            $primaryLitter->setGestationPeriod($secondaryLitter->getGestationPeriod()); $areAnyValuesUpdated = true;
        }


        $isGeneDiversityUpdated = false;
        if ($primaryLitter->getHeterosis() === null && $secondaryLitter->getHeterosis() !== null) {
            $primaryLitter->setHeterosis($secondaryLitter->getHeterosis());
            $isGeneDiversityUpdated = true; $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getRecombination() === null && $secondaryLitter->getRecombination() !== null) {
            $primaryLitter->setRecombination($secondaryLitter->getRecombination());
            $isGeneDiversityUpdated = true; $areAnyValuesUpdated = true;
        }

        if ($isGeneDiversityUpdated) {
            if ($primaryLitter->isUpdatedGeneDiversity()) {
                //Mark updatedGeneDiversity = false, so the geneDiversity values can be double checked more easily.
                $primaryLitter->setUpdatedGeneDiversity(false);
                $areAnyValuesUpdated = true;
            }
        }


        if ($areAnyValuesUpdated) {
            $this->em->persist($primaryLitter);
        }

        if ($areAnySecondaryValuesUpdated) {
            $this->em->persist($secondaryLitter);
        }

        //Flush only at the end of the successful merge.

        return true;
    }


    /**
     * @param array $results
     * @return bool
     */
    private function processSelectResults(array $results)
    {
        if (count($results) === 0) {
            $this->writeLn('No duplicates found!');
            return true;
        }


        $successFulMergeCount = 0;
        $failedMergesCount = 0;
        $this->litterIdsForWhichToMatchMates = [];

        $this->startProgressBar(count($results));
        foreach ($results as $result) {
            $primaryLitterId = $result['primary_litter_id'];
            $secondaryLitterId = $result['secondary_litter_id'];

            $isMergeSuccessful = $this->mergePairsById($primaryLitterId, $secondaryLitterId);
            if ($isMergeSuccessful) {

                $recordToDeleteId = ArrayUtil::get('record_to_delete_id', $result);
                $columnNameFromWhichToDelete = ArrayUtil::get('column_from_which_to_delete' , $result);
                $tableFromWhichToDelete = ArrayUtil::get('table_from_which_to_delete', $result);

                if ($recordToDeleteId !== null && is_string($tableFromWhichToDelete) && is_string($columnNameFromWhichToDelete)) {
                   $this->deleteRecords($recordToDeleteId, $columnNameFromWhichToDelete, [$tableFromWhichToDelete]);
                }

                $successFulMergeCount++;
            } else {
                $failedMergesCount++;
            }

            $this->advanceProgressBar('Merges failed|done: '.$failedMergesCount.'|'.$successFulMergeCount);
        }

        if (count($this->litterIdsForWhichToMatchMates) <= self::MATCH_LITTERS_WITH_MATES_BATCH_LIMIT) {
            foreach ($this->litterIdsForWhichToMatchMates as $primaryLitterId) {
                LitterUtil::matchMatchingMates($this->conn,false, $primaryLitterId);
            }
        } else {
            LitterUtil::matchMatchingMates($this->conn,false);
        }

        $this->endProgressBar();

        return $failedMergesCount === 0;
    }


    /**
     * @param CommandUtil|null $cmdUtil
     * @return bool
     */
    public function mergeDoubleAndTripleDuplicateImportedLitters(CommandUtil $cmdUtil = null)
    {
        $this->setCmdUtil($cmdUtil);
        $mergeCount = $this->mergeDuplicateImportedLittersInSetOf2($cmdUtil);
        $mergeCount += $this->mergeTripleDuplicateImportedLitters($cmdUtil);
        return $mergeCount;
    }


    /**
     * @param CommandUtil|null $cmdUtil
     * @return bool
     */
    public function mergeDuplicateImportedLittersInSetOf2(CommandUtil $cmdUtil = null)
    {
        $this->setCmdUtil($cmdUtil);

        $this->writeLn('Merging duplicate IMPORTED litters with identical mother, litterDate and primary values ...');

        $sql = "SELECT main.id as primary_litter_id, s.id as secondary_litter_id
                    FROM litter main
                      INNER JOIN (
                          SELECT
                            DENSE_RANK() OVER (PARTITION BY l.animal_mother_id, l.litter_date, l.stillborn_count, l.born_alive_count
                              ORDER BY l.id ASC) AS rank,
                            l.id
                          --l.litter_date, l.animal_mother_id, l.stillborn_count, l.born_alive_count, l.litter_ordinal, l.birth_interval
                          FROM litter l
                            INNER JOIN (
                                         SELECT litter_date, animal_mother_id FROM litter
                                           INNER JOIN declare_nsfo_base ON litter.id = declare_nsfo_base.id
                                         WHERE request_state = 'IMPORTED' AND is_abortion = FALSE AND is_pseudo_pregnancy = FALSE
                                         GROUP BY litter_date, animal_mother_id, stillborn_count, born_alive_count
                                         HAVING COUNT(*) = 2
                                       )g ON g.litter_date = l.litter_date AND g.animal_mother_id = l.animal_mother_id
                          ORDER BY g.animal_mother_id, g.litter_date
                          )g ON g.id = main.id
                      INNER JOIN litter s
                          ON s.animal_mother_id = main.animal_mother_id AND s.litter_date = main.litter_date
                         AND s.stillborn_count = main.stillborn_count AND s.born_alive_count = main.born_alive_count
                      INNER JOIN declare_nsfo_base bm ON bm.id = main.id
                      INNER JOIN declare_nsfo_base bs ON bs.id = s.id
                    WHERE g.rank = 1 AND bm.request_state = 'IMPORTED' AND bs.request_state = 'IMPORTED' AND s.id <> main.id";
        $results = $this->conn->query($sql)->fetchAll();

        return $this->processSelectResults($results);
    }


    public function mergeTripleDuplicateImportedLitters(CommandUtil $cmdUtil = null)
    {
        $this->setCmdUtil($cmdUtil);

        $this->writeLn('Merging TRIPLE IMPORTED litters with identical mother, litterDate and primary values ...');

        $sql = "SELECT main.id as primary_litter_id, s.id as secondary_litter_id, t.id as tertiary_litter_id
                FROM litter main
                  INNER JOIN (
                               SELECT
                                 DENSE_RANK() OVER (PARTITION BY l.animal_mother_id, l.litter_date, l.stillborn_count, l.born_alive_count
                                   ORDER BY l.id ASC) AS rank,
                                 l.id
                               --l.litter_date, l.animal_mother_id, l.stillborn_count, l.born_alive_count, l.litter_ordinal, l.birth_interval
                               FROM litter l
                                 INNER JOIN (
                                              SELECT litter_date, animal_mother_id FROM litter
                                                INNER JOIN declare_nsfo_base ON litter.id = declare_nsfo_base.id
                                              WHERE request_state = 'IMPORTED' AND is_abortion = FALSE AND is_pseudo_pregnancy = FALSE
                                              GROUP BY litter_date, animal_mother_id, stillborn_count, born_alive_count
                                              HAVING COUNT(*) = 3
                                            )g ON g.litter_date = l.litter_date AND g.animal_mother_id = l.animal_mother_id
                               ORDER BY g.animal_mother_id, g.litter_date
                             )g ON g.id = main.id
                  INNER JOIN litter s
                    ON s.animal_mother_id = main.animal_mother_id AND s.litter_date = main.litter_date
                       AND s.stillborn_count = main.stillborn_count AND s.born_alive_count = main.born_alive_count
                  INNER JOIN litter t
                    ON t.animal_mother_id = main.animal_mother_id AND t.litter_date = main.litter_date
                       AND t.stillborn_count = main.stillborn_count AND t.born_alive_count = main.born_alive_count
                  INNER JOIN declare_nsfo_base bm ON bm.id = main.id
                  INNER JOIN declare_nsfo_base bs ON bs.id = s.id
                  INNER JOIN declare_nsfo_base bt ON bt.id = t.id
                WHERE g.rank = 1 AND bm.request_state = 'IMPORTED' AND bs.request_state = 'IMPORTED' 
                      AND bt.request_state = 'IMPORTED'
                      AND s.id <> main.id AND t.id <> main.id
                      AND s.id < t.id --Note that the rank is ascending by rank";
        $results = $this->conn->query($sql)->fetchAll();


        //Split up the triple results into two sets of doubles
        $formattedResults = [];
        foreach ($results as $result) {
            $primaryLitterId = $result['primary_litter_id'];
            $secondaryLitterId = $result['secondary_litter_id'];
            $tertiaryLitterId = $result['tertiary_litter_id'];

            $formattedResults[] = [
                'primary_litter_id' => $primaryLitterId,
                'secondary_litter_id' => $secondaryLitterId,
            ];

            $formattedResults[] = [
                'primary_litter_id' => $primaryLitterId,
                'secondary_litter_id' => $tertiaryLitterId,
            ];
        }

        return $this->processSelectResults($formattedResults);
    }


    /**
     * @param CommandUtil|null $cmdUtil
     * @return bool
     */
    public function mergeDuplicateLittersWithOnlySingleStillborn(CommandUtil $cmdUtil = null)
    {
        $this->setCmdUtil($cmdUtil);

        $this->writeLn('Merging duplicate litters with only single stillborns ...');

        $sql = "SELECT main.id as primary_litter_id, s.id as secondary_litter_id,
                       sbs.id as record_to_delete_id,
                       'stillborn' as table_from_which_to_delete,
                       'id' as column_from_which_to_delete --sbs.*, sbm.*
                FROM litter main
                  INNER JOIN (
                      SELECT
                        DENSE_RANK() OVER (PARTITION BY l.animal_mother_id, l.litter_date, l.stillborn_count, l.born_alive_count
                          ORDER BY l.id ASC) AS rank,
                        l.id, g.max_weight
                      --l.litter_date, l.animal_mother_id, l.stillborn_count, l.born_alive_count, l.litter_ordinal, l.birth_interval
                      FROM litter l
                        INNER JOIN (
                                     SELECT litter_date, animal_mother_id, MAX(sb.weight) as max_weight FROM litter
                                       INNER JOIN declare_nsfo_base ON litter.id = declare_nsfo_base.id
                                       INNER JOIN stillborn sb ON sb.litter_id = litter.id
                                     WHERE request_state <> 'REVOKED' AND is_abortion = FALSE AND is_pseudo_pregnancy = FALSE
                                       AND stillborn_count = 1 AND born_alive_count = 0
                                     GROUP BY litter_date, animal_mother_id, stillborn_count, born_alive_count
                                     HAVING COUNT(*) = 2
                                   )g ON g.litter_date = l.litter_date AND g.animal_mother_id = l.animal_mother_id
                      ORDER BY g.animal_mother_id, g.litter_date
                      )g ON g.id = main.id
                  INNER JOIN litter s
                      ON s.animal_mother_id = main.animal_mother_id AND s.litter_date = main.litter_date
                     AND s.stillborn_count = main.stillborn_count AND s.born_alive_count = main.born_alive_count
                  INNER JOIN declare_nsfo_base bm ON bm.id = main.id
                  INNER JOIN declare_nsfo_base bs ON bs.id = s.id
                  INNER JOIN stillborn sbs ON sbs.litter_id = s.id
                  INNER JOIN stillborn sbm ON sbm.litter_id = main.id
                WHERE sbm.weight = g.max_weight --Prioritize stillborn litter with
                      AND bm.request_state <> 'REVOKED' AND bs.request_state <> 'REVOKED' AND s.id <> main.id";
        $results = $this->conn->query($sql)->fetchAll();

        return $this->processSelectResults($results);
    }
}