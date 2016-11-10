<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\NullChecker;

/**
 * Class BlindnessFactorRepository
 * @package AppBundle\Entity
 */
class BlindnessFactorRepository extends BaseRepository {


    /**
     * @param $animalId
     * @param bool $ignoreNull
     */
    public function setLatestBlindnessFactorByAnimalId($animalId, $ignoreNull = true)
    {
        $latestBlindnessFactor = $this->getLatestBlindnessFactors($animalId);
        if($latestBlindnessFactor == null && $ignoreNull) { return; }

        $sql = "UPDATE animal SET blindness_factor = '".$latestBlindnessFactor."' WHERE id = ".$animalId;
        $this->getManager()->getConnection()->exec($sql);
    }


    public function setLatestBlindnessFactorsOnAllAnimals(CommandUtil $cmdUtil = null)
    {
        /** @var BlindnessFactorRepository $blindnessFactorRepository */
        $blindnessFactorRepository = $this->getManager()->getRepository(BlindnessFactor::class);
        $latestBlindnessFactors = $blindnessFactorRepository->getLatestBlindnessFactors();
        $animalIds = array_keys($latestBlindnessFactors);

        $currentBlindnessFactorValueInAnimals = $this->getBlindnessFactorValueInAnimals();

        if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt(count($animalIds)+1, 1); }

        $newCount = 0;
        foreach ($animalIds as $animalId) {
            //Check if value has changed before updating
            if(Utils::getNullCheckedArrayValue($animalId, $currentBlindnessFactorValueInAnimals) != $latestBlindnessFactors[$animalId]) {
                $sql = "UPDATE animal SET blindness_factor = '".$latestBlindnessFactors[$animalId]."' WHERE id = ".$animalId;
                $this->getManager()->getConnection()->exec($sql);
                $newCount++;
                if($cmdUtil != null) { $cmdUtil->advanceProgressBar(1); }
            }
        }

        if($cmdUtil != null) {
            $cmdUtil->setProgressBarMessage($newCount.' blindnessFactor values updated');
            $cmdUtil->setEndTimeAndPrintFinalOverview();
        }
    }


    /**
     * If animalId == null, return blindnessFactors of all animals
     *
     * @param null $animalId
     * @return array
     */
    public function getLatestBlindnessFactors($animalId = null)
    {
        $filter = $animalId == null ? '' : 'WHERE b.animal_id = '.$animalId;
        
        $sql = "SELECT b.animal_id, b.blindness_factor, b.log_date FROM blindness_factor b
                INNER JOIN (
                    SELECT animal_id, MAX(log_date) as log_date FROM blindness_factor
                    GROUP BY animal_id
                    )x ON x.animal_id = b.animal_id AND b.log_date = x.log_date ".$filter;

        //If only one animal
        if($animalId != null) {
            $result = $this->getManager()->getConnection()->query($sql)->fetch();
            return array_key_exists('blindness_factor', $result) ? $result['blindness_factor'] : null;
        }

        //If all animals

        $results = $this->getManager()->getConnection()->query($sql)->fetchAll();
        $searchArray = [];
        foreach ($results as $result) {
            $searchArray[$result['animal_id']] = $result['blindness_factor'];
        }

        return $searchArray;
    }


    /**
     * @return array
     */
    public function getBlindnessFactorValueInAnimals()
    {
        $sql = "SELECT id, blindness_factor FROM animal WHERE blindness_factor NOTNULL";
        $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

        $searchArray = [];
        foreach ($results as $result) {
            $searchArray[$result['id']] = $result['blindness_factor'];
        }
        return $searchArray;
    }
    
}