<?php

namespace AppBundle\Entity;
use AppBundle\Constant\ReportLabel;
use AppBundle\Enumerator\BreedTrait;

/**
 * Class GeneticBaseRepository
 * @package AppBundle\Entity
 */
class  GeneticBaseRepository extends BaseRepository {

    const MIN_RELIABILITY_FOR_GENETIC_BASE = 0.16; //min accuracy = sqrt(0.16) = 0.4

    /**
     * @param int $year
     * @return GeneticBase
     */
    public function updateGeneticBases($year)
    {
        /* generate most recent values */
        $muscleThicknessGeneticBase = $this->getGeneticBaseOfTrait($year, BreedTrait::MUSCLE_THICKNESS);
        $growthGeneticBase = $this->getGeneticBaseOfTrait($year, BreedTrait::GROWTH);
        $fatGeneticBase = $this->getGeneticBaseOfTrait($year, BreedTrait::FAT);

        $areAnyValuesUpdated = false;

        $geneticBase = $this->findOneBy(['year' => $year]);

        if (!($geneticBase instanceof GeneticBase)) {
            //generate new geneticBase record
            $geneticBase = new GeneticBase($year, $muscleThicknessGeneticBase, $growthGeneticBase, $fatGeneticBase);
            $areAnyValuesUpdated = true;
        } else {
            //Old record already exists, update values if necessary
            if($muscleThicknessGeneticBase != $geneticBase->getMuscleThickness()) {
                $geneticBase->setMuscleThickness($muscleThicknessGeneticBase);
                $areAnyValuesUpdated = true;
            }
            if($growthGeneticBase != $geneticBase->getGrowth()) {
                $geneticBase->setGrowth($growthGeneticBase);
                $areAnyValuesUpdated = true;
            }
            if($fatGeneticBase != $geneticBase->getFat()) {
                $geneticBase->setFat($fatGeneticBase);
                $areAnyValuesUpdated = true;
            }
        }

        if($areAnyValuesUpdated) {
            $this->getManager()->persist($geneticBase);
            $this->getManager()->flush();
        }

        return $geneticBase;
    }


    /**
     * @param $year
     * @return null
     */
    public function getGeneticBaseOfTrait($year, $trait)
    {
        if(!BreedTrait::contains(strtoupper($trait))) { return null; }

        $trait = strtolower($trait);

        $sql = "SELECT EXTRACT(YEAR FROM date_of_birth), AVG(b.".$trait.") as ".$trait."_average FROM breed_values_set b
                  INNER JOIN animal a ON b.animal_id = a.id
                  WHERE EXTRACT(YEAR FROM date_of_birth) = ".$year."
                  AND b.".$trait."_reliability >= ".self::MIN_RELIABILITY_FOR_GENETIC_BASE."
                GROUP BY EXTRACT(YEAR FROM date_of_birth)";
        $results = $this->getManager()->getConnection()->query($sql)->fetch();

        if(count($results) == 0) { return null; }

        return $results[$trait.'_average'];
    }

}