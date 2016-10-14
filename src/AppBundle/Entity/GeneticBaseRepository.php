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
    public function getNullCheckedGeneticBases($year)
    {
        if($year == null) { return null; }
        
        /** @var GeneticBase $geneticBases */
        $geneticBases = $this->findOneBy(['year' => $year]);

        //If geneticBase is null, they probably have not been generated yet.
        if($geneticBases == null) {
            $geneticBases = $this->updateGeneticBases($year);
        }

        return $geneticBases;
    }

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

        //Remove geneticBases that would be update with any blank values
        if($muscleThicknessGeneticBase == null || $growthGeneticBase == null || $fatGeneticBase == null) {
            if ($geneticBase instanceof GeneticBase) {
                $this->remove($geneticBase);
            }

            //Null check result
            return null;
        }


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


    /**
     * @return int
     */
    public function getLatestYear()
    {
        $sql = "SELECT MAX(year) FROM genetic_base;";
        $result = $this->getManager()->getConnection()->query($sql)->fetch();

        if($result == null) { return null; }

        return $result['max'];
    }


    /**
     * @return array
     */
    public function getAllYears()
    {
        $sql = "SELECT DISTINCT(year) as year FROM genetic_base ORDER BY year ASC";
        $sqlResults = $this->getManager()->getConnection()->query($sql)->fetchAll();

        $result = array();

        foreach($sqlResults as $sqlResult) {
            $result[] = $sqlResult['year'];
        }

        return $result;
    }
    
    
}