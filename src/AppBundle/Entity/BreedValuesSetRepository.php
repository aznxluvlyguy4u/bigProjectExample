<?php

namespace AppBundle\Entity;
use AppBundle\Constant\BreedValueLabel;
use AppBundle\Report\PedigreeCertificate;
use AppBundle\Util\BreedValueUtil;
use AppBundle\Util\NullChecker;

/**
 * Class BreedValuesSetRepository
 * @package AppBundle\Entity
 */
class BreedValuesSetRepository extends BaseRepository {

    /**
     * If the breedValues for multiple animals is required,
     * it is advised to retrieve the geneticBase once and pass the values as a variable,
     * to minimize calls to the database.
     * 
     * @param int $animalId
     * @param int $year
     * @param GeneticBase $geneticBases
     * @return array
     */
    public function getBreedValuesCorrectedByGeneticBaseWithAccuracies($animalId, $year, $geneticBases = null)
    {
        if($geneticBases == null) {
            /** @var GeneticBaseRepository $geneticBaseRepository */
            $geneticBaseRepository = $this->getManager()->getRepository(GeneticBase::class);
            $geneticBases = $geneticBaseRepository->getNullCheckedGeneticBases($year);
        }

        //If there are no genetic bases, then the corrected values cannot be calculated
        if($geneticBases == null) { return null; }

        //Default values
        $muscleThicknessValue = null;
        $growthValue = null;
        $fatValue = null;

        $muscleThicknessAccuracy = null;
        $growthAccuracy = null;
        $fatAccuracy = null;

        $muscleThicknessReliability = null;
        $growthReliability = null;
        $fatReliability = null;

        $isGetFormattedAccuracies = false;


        if($animalId != null && $year != null) {
            $sql = "SELECT * FROM breed_values_set WHERE animal_id = ".$animalId." AND EXTRACT(YEAR FROM generation_date) = ".$year;
            $results = $this->getManager()->getConnection()->query($sql)->fetch();

            if(NullChecker::floatIsNotZero($results['muscle_thickness_reliability'])) {
                $muscleThicknessValue = $results['muscle_thickness'] - $geneticBases->getMuscleThickness();
                $muscleThicknessReliability = $results['muscle_thickness_reliability'];
                $muscleThicknessAccuracy = BreedValueUtil::getAccuracyFromReliability($muscleThicknessReliability, $isGetFormattedAccuracies);
            }

            if(NullChecker::floatIsNotZero($results['growth_reliability'])) {
                $growthValue = $results['growth'] - $geneticBases->getGrowth();
                $growthReliability = $results['growth_reliability'];
                $growthAccuracy = BreedValueUtil::getAccuracyFromReliability($growthReliability, $isGetFormattedAccuracies);
            }

            if(NullChecker::floatIsNotZero($results['fat_reliability'])) {
                $fatValue = $results['fat'] - $geneticBases->getFat();
                $fatReliability = $results['fat_reliability'];
                $fatAccuracy = BreedValueUtil::getAccuracyFromReliability($fatReliability, $isGetFormattedAccuracies);
            }
        }

        return [
            BreedValueLabel::GROWTH => $growthValue,
            BreedValueLabel::MUSCLE_THICKNESS => $muscleThicknessValue,
            BreedValueLabel::FAT => $fatValue,
            BreedValueLabel::GROWTH_ACCURACY => $growthAccuracy,
            BreedValueLabel::MUSCLE_THICKNESS_ACCURACY => $muscleThicknessAccuracy,
            BreedValueLabel::FAT_ACCURACY => $fatAccuracy,
            BreedValueLabel::GROWTH_RELIABILITY => $growthReliability,
            BreedValueLabel::MUSCLE_THICKNESS_RELIABILITY => $muscleThicknessReliability,
            BreedValueLabel::FAT_RELIABILITY => $fatReliability
        ];
    }

}