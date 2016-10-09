<?php

namespace AppBundle\Entity;
use AppBundle\Constant\ReportLabel;
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
        $em = $this->getManager();
    
        $sql = "SELECT * FROM breed_values_set WHERE animal_id = ".$animalId." AND EXTRACT(YEAR FROM generation_date) = ".$year;
        $results = $em->getConnection()->query($sql)->fetch();

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

        $isGetFormattedAccuracies = false;

        if(NullChecker::floatIsNotZero($results['muscle_thickness_reliability'])) {
            $muscleThicknessValue = $results['muscle_thickness'] - $geneticBases->getMuscleThickness();
            $muscleThicknessAccuracy = BreedValueUtil::getAccuracyFromReliability($results['muscle_thickness_reliability'], $isGetFormattedAccuracies);
        }

        if(NullChecker::floatIsNotZero($results['growth_reliability'])) {
            $growthValue = $results['growth'] - $geneticBases->getGrowth();
            $growthAccuracy = BreedValueUtil::getAccuracyFromReliability($results['growth_reliability'], $isGetFormattedAccuracies);
        }

        if(NullChecker::floatIsNotZero($results['fat_reliability'])) {
            $fatValue = $results['fat'] - $geneticBases->getFat();
            $fatAccuracy = BreedValueUtil::getAccuracyFromReliability($results['fat_reliability'], $isGetFormattedAccuracies);
        }

        return [
            ReportLabel::GROWTH => $growthValue,
            ReportLabel::MUSCLE_THICKNESS => $muscleThicknessValue,
            ReportLabel::FAT => $fatValue,
            ReportLabel::GROWTH_ACCURACY => $growthAccuracy,
            ReportLabel::MUSCLE_THICKNESS_ACCURACY => $muscleThicknessAccuracy,
            ReportLabel::FAT_ACCURACY => $fatAccuracy
        ];
    }

}