<?php

namespace AppBundle\Service\DataFix;


use AppBundle\Util\BreedCodeUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\SqlUtil;

class OffspringBreedCodeRecalculator extends DataFixServiceBase
{
    /** @var int */
    private $recalculationCount;
    /** @var array */
    private $animalIdsRecalculatedBreedCodes;


    /**
     * @param CommandUtil $cmdUtil
     * @return int|null
     */
    public function recalculateBreedCodesOfOffspringOfGivenAnimalById(CommandUtil $cmdUtil)
    {
        $question = 'Insert id or uln of animal for which the children\'s breedcodes need to be recalculated';
        $animal = DoctrineUtil::askForAnimalByIdOrUln($cmdUtil, $this->getManager(), $question);
        $totalRecalculationCount = $this->recursiveRecalculate($animal->getId());
        $this->getLogger()->notice('Total recalculation count: '.$totalRecalculationCount);

        return $totalRecalculationCount;
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
            $isUpdated = BreedCodeUtil::updateBreedCodeBySql($this->getConnection(), $animalId, $this->getLogger());
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