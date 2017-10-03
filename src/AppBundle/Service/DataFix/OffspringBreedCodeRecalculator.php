<?php

namespace AppBundle\Service\DataFix;


use AppBundle\Util\BreedCodeUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\SqlUtil;

class OffspringBreedCodeRecalculator extends DataFixServiceBase
{
    /** @var int */
    private $recalculationCount;
    /** @var array */
    private $animalIdsRecalculatedBreedCodes;


    public function recalculateBreedCodesOfOffspringOfGivenAnimalById(CommandUtil $cmdUtil)
    {

    }


    private function resetClassVariables()
    {
        $this->recalculationCount = 0;
        $this->animalIdsRecalculatedBreedCodes = [];
    }


    /**
     * @param int $animalId
     * @param bool $onlyRecalculateForChildren
     * @param bool $resetCounter
     * @return int|null
     */
    public function recursiveRecalculate($animalId, $onlyRecalculateForChildren = true, $resetCounter = true)
    {
        if ($resetCounter) {
            $this->resetClassVariables();
        }

        if (!is_int($animalId) && !ctype_digit($animalId)) {
            return null;
        }


        $recalculateForChildren = false;
        if ($onlyRecalculateForChildren) {
            $recalculateForChildren = true;

        } else {
            $isUpdated = BreedCodeUtil::updateBreedCodeBySql($this->getConnection(), $animalId);
            if ($isUpdated) {
                $this->recalculationCount++;
                $recalculateForChildren = true;
            }
        }

        if($recalculateForChildren) {
            foreach ($this->getOffspringIds($animalId) as $childId) {
                if (!key_exists($childId, $this->animalIdsRecalculatedBreedCodes)) {
                    $this->recursiveRecalculate($childId, false, false);
                    $this->animalIdsRecalculatedBreedCodes[$childId] = $childId;
                }
            }
        }

        return $this->recalculationCount;
    }


    /**
     * @param int $animalId
     * @return array
     */
    private function getOffspringIds($animalId)
    {
        if (!is_int($animalId) && !ctype_digit($animalId)) {
            return [];
        }

        $sql = "SELECT id FROM animal WHERE parent_mother_id = $animalId OR parent_father_id = $animalId";
        $results = $this->getConnection()->query($sql)->fetchAll();

        return SqlUtil::getSingleValueGroupedSqlResults('id', $results, true, false);
    }


}