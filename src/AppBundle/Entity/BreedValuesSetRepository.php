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
     * @param int $animalId
     * @return array
     */
    public function getGrowthMuscleThicknessAndFatWithAccuracies($animalId)
    {
        $em = $this->getManager();
    
        $sql = "SELECT * FROM breed_values_set WHERE animal_id = ".$animalId;
        $results = $em->getConnection()->query($sql)->fetch();

        //Default values
        $muscleThicknessValue = null;
        $growthValue = null;
        $fatValue = null;

        $muscleThicknessAccuracy = null;
        $growthAccuracy = null;
        $fatAccuracy = null;

        if(NullChecker::floatIsNotZero($results['muscle_thickness_reliability'])) {
            $muscleThicknessValue = round($results['muscle_thickness'], PedigreeCertificate::MUSCLE_THICKNESS_DECIMAL_ACCURACY);
            $muscleThicknessAccuracy = BreedValueUtil::getAccuracyFromReliability($results['muscle_thickness_reliability'], true);
        }

        if(NullChecker::floatIsNotZero($results['growth_reliability'])) {
            $growthValue = round($results['growth'], PedigreeCertificate::GROWTH_DECIMAL_ACCURACY);
            $growthAccuracy = BreedValueUtil::getAccuracyFromReliability($results['growth_reliability'], true);
        }

        if(NullChecker::floatIsNotZero($results['fat_reliability'])) {
            $fatValue = round($results['fat'], PedigreeCertificate::FAT_THICKNESS_DECIMAL_ACCURACY);
            $fatAccuracy = BreedValueUtil::getAccuracyFromReliability($results['fat_reliability'], true);
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