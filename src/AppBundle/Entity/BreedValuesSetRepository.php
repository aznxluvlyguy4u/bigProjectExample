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
    public function getBreedValuesWithAccuracies($animalId)
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

        $isGetFormattedAccuracies = false;

        if(NullChecker::floatIsNotZero($results['muscle_thickness_reliability'])) {
            $muscleThicknessValue = $results['muscle_thickness'];
            $muscleThicknessAccuracy = BreedValueUtil::getAccuracyFromReliability($results['muscle_thickness_reliability'], $isGetFormattedAccuracies);
        }

        if(NullChecker::floatIsNotZero($results['growth_reliability'])) {
            $growthValue = $results['growth'];
            $growthAccuracy = BreedValueUtil::getAccuracyFromReliability($results['growth_reliability'], $isGetFormattedAccuracies);
        }

        if(NullChecker::floatIsNotZero($results['fat_reliability'])) {
            $fatValue = $results['fat'];
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