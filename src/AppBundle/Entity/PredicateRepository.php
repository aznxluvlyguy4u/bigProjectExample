<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Util\CommandUtil;

/**
 * Class PredicateRepository
 * @package AppBundle\Entity
 */
class PredicateRepository extends BaseRepository {

    const VALUE = 'value';
    const SCORE = 'score';


    /**
     * @param $animalId
     * @param bool $ignoreNullPredicateValue
     */
    public function setLatestPredicateValueByAnimalId($animalId, $ignoreNullPredicateValue = true)
    {
        if(!is_int($animalId)) { return; }

        $predicateData = $this->getLatestDataInPredicates($animalId);
        if($predicateData == null) { return; }

        $value = $predicateData[self::VALUE];
        $score = $predicateData[self::SCORE];

        $value = $value != null ? "'".$value."'" : 'NULL';
        $score = $score != null ? $score : 'NULL';

        if($value == null && $ignoreNullPredicateValue) { return; }

        $sql = "UPDATE animal SET predicate = ".$value.", predicate_score = ".$score." WHERE id = ".$animalId;
        $this->getManager()->getConnection()->exec($sql);
    }
    
    
    public function setLatestPredicateValuesOnAllAnimals(CommandUtil $cmdUtil = null)
    {
        $latestDataInPredicates = $this->getLatestDataInPredicates();
        $animalIds = array_keys($latestDataInPredicates);

        $currentPredicateValuesInAnimals = $this->getPredicateValuesInAnimals();

        if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt(count($animalIds)+1, 1); }

        $newCount = 0;
        foreach ($animalIds as $animalId) {
            //Check if value has changed before updating
            $dataInAnimal = Utils::getNullCheckedArrayValue($animalId, $currentPredicateValuesInAnimals);
            $dataInPredicate = Utils::getNullCheckedArrayValue($animalId, $latestDataInPredicates);
            $valueInPredicate = $dataInPredicate[self::VALUE];
            $scoreInPredicate = $dataInPredicate[self::SCORE];
            
            $isUpdateAnimal = true;
            if($dataInAnimal != null) {
                $valueInAnimal = $dataInAnimal[self::VALUE];
                $scoreInAnimal = $dataInAnimal[self::SCORE];
                if($valueInAnimal == $valueInPredicate && $scoreInAnimal == $scoreInPredicate) {
                    $isUpdateAnimal = false;
                }
            }

            if($isUpdateAnimal) {
                $value = $valueInPredicate != null ? "'".$valueInPredicate."'" : 'NULL';
                $score = $scoreInPredicate != null ? $scoreInPredicate : 'NULL';
                $sql = "UPDATE animal SET predicate = ".$value.", predicate_score = ".$score." WHERE id = ".$animalId;
                $this->getManager()->getConnection()->exec($sql);
                $newCount++;
            }
            if($cmdUtil != null) { $cmdUtil->advanceProgressBar(1, 'Animals updated: '.$newCount); }
        }

        if($cmdUtil != null) {
            $cmdUtil->setProgressBarMessage($newCount.' predicate values updated in Animal');
            $cmdUtil->setEndTimeAndPrintFinalOverview();
        }
    }


    /**
     * If animalId == null, return Predicates of all animals
     *
     * @param null $animalId
     * @return array
     */
    public function getLatestDataInPredicates($animalId = null)
    {
        $filter = $animalId == null ? '' : 'WHERE p.animal_id = '.$animalId;

        $sql = "SELECT p.animal_id, p.predicate, p.predicate_score, p.start_date, p.end_date FROM predicate p
                  INNER JOIN (
                               SELECT animal_id, MAX(start_date) as start_date FROM predicate
                               GROUP BY animal_id
                             )x ON x.animal_id = p.animal_id AND x.start_date = p.start_date ".$filter;

        //If only one animal
        if($animalId != null) {
            $result = $this->getManager()->getConnection()->query($sql)->fetch();
            
            return array_key_exists('predicate', $result) ? [
                self::VALUE => $result['predicate'],
                self::SCORE => $result['predicate_score'],
            ] : null;
        }

        //If all animals

        $results = $this->getManager()->getConnection()->query($sql)->fetchAll();
        $searchArray = [];
        foreach ($results as $result) {
            $searchArray[$result['animal_id']] = [
                self::VALUE => $result['predicate'],
                self::SCORE => $result['predicate_score'],
            ];
        }

        return $searchArray;
    }


    /**
     * @return array
     */
    public function getPredicateValuesInAnimals()
    {
        $sql = "SELECT id, predicate, predicate_score FROM animal WHERE predicate NOTNULL";
        $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

        $searchArray = [];
        foreach ($results as $result) {
            $searchArray[$result['id']] = [
                self::VALUE => $result['predicate'],
                self::SCORE => $result['predicate_score'],
            ];
        }
        return $searchArray;
    }


    /**
     * Note! Only designed for running correctly during 2016nov import.
     */
    public function fillPredicateValuesInAnimalForPredicatesWithoutStartDates(CommandUtil $cmdUtil = null)
    {
        $sql = "SELECT p.* FROM predicate p
                LEFT JOIN animal a ON p.animal_id = a.id
                WHERE a.predicate ISNULL";
        $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

        if(count($results) == 0) { return; }
        
        if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt(count($results)+1, 1); }
        
        foreach ($results as $result) {
            $animalId = $result['animal_id'];
            $valueInPredicate = $result['predicate'];
            $scoreInPredicate = $result['predicate_score'];
            $value = $valueInPredicate != null ? "'".$valueInPredicate."'" : 'NULL';
            $score = $scoreInPredicate != null ? $scoreInPredicate : 'NULL';

            $sql = "UPDATE animal SET predicate = ".$value.", predicate_score = ".$score." WHERE id = ".$animalId;
            $this->getManager()->getConnection()->exec($sql);

            if($cmdUtil != null) { $cmdUtil->advanceProgressBar(1); }
        }
        if($cmdUtil != null) {
            $cmdUtil->setProgressBarMessage(count($results).' predicate values updated in Animals from Predicate without startDate');
            $cmdUtil->setEndTimeAndPrintFinalOverview();
        }
    }

}