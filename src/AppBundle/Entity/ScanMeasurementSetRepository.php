<?php

namespace AppBundle\Entity;

use AppBundle\model\request\ScanMeasurementsValues;
use AppBundle\model\ScanMeasurementsUnlinkedData;

/**
 * Class ScanMeasurementSetRepository
 * @package AppBundle\Entity
 */
class ScanMeasurementSetRepository  extends MeasurementRepository{

    const INSERT_BATCH_SIZE = 100;

    /**
     * @param array|ScanMeasurementsUnlinkedData[] $unlinkedDataSets
     */
    function persistNewByUnlinkedDataSets(array $unlinkedDataSets)
    {
        if (empty($unlinkedDataSets)) {
            return;
        }

        $loopCount = 0;
        foreach ($unlinkedDataSets as $unlinkedDataSet) {

            if ($this->findOneBy([
                'animalIdAndDate' => $unlinkedDataSet->animalIdAndDate,
                'isActive' => true
            ])) {
                continue;
            }

            /** @var Animal $animalByReference */
            $animalByReference = $this->getEntityManager()->getReference(Animal::class, $unlinkedDataSet->animalId);

            /** @var Inspector|null $inspector */
            $inspector = $unlinkedDataSet->scanInspectorId != null ?
                $this->getEntityManager()->getReference(Inspector::class, $unlinkedDataSet->scanInspectorId)
            : null;

            /** @var Weight $scanWeightByReference */
            $scanWeightByReference = $this->getEntityManager()->getReference(Weight::class, $unlinkedDataSet->scanWeightId);
            /** @var BodyFat $bodyFatByReference */
            $bodyFatByReference = $this->getEntityManager()->getReference(BodyFat::class, $unlinkedDataSet->bodyFatId);
            /** @var MuscleThickness $muscleThicknessByReference */
            $muscleThicknessByReference = $this->getEntityManager()->getReference(MuscleThickness::class, $unlinkedDataSet->muscleThicknessId);

            $scanSet = new ScanMeasurementSet();
            $scanSet
                ->setAnimal($animalByReference)
                ->setScanWeight($scanWeightByReference)
                ->setBodyFat($bodyFatByReference)
                ->setMuscleThickness($muscleThicknessByReference)
                // Measurement entity Setters
                ->setMeasurementDate($unlinkedDataSet->measurementDate)
                ->setInspector($inspector)
                ->setAnimalIdAndDateByAnimalAndDateTime($animalByReference, $unlinkedDataSet->measurementDate)
            ;

            $this->getEntityManager()->persist($scanSet);

            if(++$loopCount%self::INSERT_BATCH_SIZE == 0) { $this->flush(); }

        }
        $this->flush();
    }

    /**
     * @return array|ScanMeasurementsUnlinkedData[]
     */
    function getUnlinkedScanDataWithMatchingInspectors(): array {
        $sqlQueries = [
            'sqlAllInspectorsExistsAndMatch' => $this->unlinkedScanDataQueryBase(true),
            'sqlAllInspectorsAreMissing' => $this->unlinkedScanDataQueryBase(
                false,
                " AND m.inspector_id ISNULL AND fat.inspector_id ISNULL AND muscle.inspector_id ISNULL "
            ),
            'sqlOneOrTwoInspectorsAreMissingButExistingInspectorsMatch' => $this->unlinkedScanDataQueryBase(
                false,
                "  AND (
      (m.inspector_id = fat.inspector_id AND muscle.inspector_id ISNULL) OR
      (m.inspector_id ISNULL AND fat.inspector_id = muscle.inspector_id) OR
      (fat.inspector_id ISNULL AND m.inspector_id = muscle.inspector_id) OR
      (m.inspector_id NOTNULL AND fat.inspector_id ISNULL AND muscle.inspector_id ISNULL) OR
      (m.inspector_id ISNULL AND fat.inspector_id NOTNULL AND muscle.inspector_id ISNULL) OR
      (m.inspector_id ISNULL AND fat.inspector_id ISNULL AND muscle.inspector_id NOTNULL)
    ) "),
        ];
        $sql = implode(' UNION ', $sqlQueries);
        return $this->retrieveMappedResults($sql);
    }

    /**
     * @param string $sql
     * @return array|ScanMeasurementsUnlinkedData[]
     */
    private function retrieveMappedResults(string $sql): array {
        $results = $this->getConnection()->query($sql)->fetchAll();
        return array_map(function(array $result) {
            return new ScanMeasurementsUnlinkedData($result);
        }, $results);
    }


    private function unlinkedScanDataQueryBase(bool $joinOnInspectorId, string $sqlWhereFilter = ""): string {

        $fatJoinOnInspector = $joinOnInspectorId ? "AND m.inspector_id = fat.inspector_id": "";
        $muscleJoinOnInspector = $joinOnInspectorId ? "AND m.inspector_id = muscle.inspector_id": "";

        return"SELECT w.animal_id,
                   m.measurement_date,
                   m.animal_id_and_date,
                   w.id                as scan_weight_id,
                   fat.id              as body_fat_id,
                   muscle.id           as muscle_thickness_id,
                   COALESCE(m.inspector_id, fat.inspector_id, muscle.inspector_id) as scan_inspector_id,
                   m.inspector_id      as scan_weight_inspector_id,
                   fat.inspector_id    as body_fat_inspector_id,
                   muscle.inspector_id as muscle_thickness_inspector_id,
                   (
                           m.inspector_id ISNULL AND fat.inspector_id ISNULL AND muscle.inspector_id ISNULL
                       ) OR (
                           (m.inspector_id = fat.inspector_id OR m.inspector_id ISNULL OR fat.inspector_id ISNULL) AND
                           (fat.inspector_id = muscle.inspector_id OR fat.inspector_id ISNULL OR muscle.inspector_id ISNULL)
                       )               as are_inspector_ids_identical
            FROM measurement m
                     INNER JOIN weight w ON w.id = m.id
                     INNER JOIN (
                SELECT animal_id_and_date,
                       id,
                       inspector_id
                FROM measurement
                WHERE type = 'BodyFat'
                  AND is_active
            ) fat ON m.animal_id_and_date = fat.animal_id_and_date 
                $fatJoinOnInspector
                     INNER JOIN (
                SELECT animal_id_and_date,
                       id,
                       inspector_id
                FROM measurement
                WHERE type = 'MuscleThickness'
                  AND is_active
            ) muscle ON m.animal_id_and_date = muscle.animal_id_and_date 
                $muscleJoinOnInspector
            WHERE m.is_active
              AND m.animal_id_and_date NOT IN (
                SELECT
                    animal_id_and_date
                FROM scan_measurement_set s
                         INNER JOIN measurement m on s.id = m.id
                ) ".$sqlWhereFilter;
    }

    /**
     * @return array|ScanMeasurementsUnlinkedData[]
     */
    function getUnlinkedScanDataWithNonMatchingInspectors(): array {
        $sql = $this->unlinkedScanDataQueryBase(false,
            "  AND NOT (
        (
                m.inspector_id ISNULL AND fat.inspector_id ISNULL AND muscle.inspector_id ISNULL
            ) OR (
                (m.inspector_id = fat.inspector_id OR m.inspector_id ISNULL OR fat.inspector_id ISNULL) AND
                (fat.inspector_id = muscle.inspector_id OR fat.inspector_id ISNULL OR muscle.inspector_id ISNULL)
            )
    ) ");
        return $this->retrieveMappedResults($sql);
    }


    /**
     * @param  Animal  $animal
     * @param  ScanMeasurementsValues  $values
     * @param  Person  $actionBy
     * @return ScanMeasurementSet
     */
    public function create(Animal $animal, ScanMeasurementsValues $values, Person $actionBy): ScanMeasurementSet
    {
        /** @var Inspector|null $inspectorByReference */
        $inspectorByReference = is_int($values->inspectorId) ?
            $this->getManager()->getReference(Inspector::class, intval($values->inspectorId)) : null;

        $animalIdAndDate = Measurement::generateAnimalIdAndDate($animal, $values->measurementDate);

        $set = new ScanMeasurementSet();

        $scanWeight = (new Weight())
            ->setIsBirthWeight(false)
            ->setWeight($values->scanWeight)
            ->setScanMeasurementSet($set)
            ->setAnimal($animal)
        ;
        $scanWeight
            ->setMeasurementDate($values->measurementDate)
            ->setAnimalIdAndDate($animalIdAndDate)
            ->setActionBy($actionBy)
        ;


        $bodyFat = (new BodyFat())
            ->setScanMeasurementSet($set)
            ->setAnimal($animal)
        ;
        $bodyFat
            ->setMeasurementDate($values->measurementDate)
            ->setAnimalIdAndDate($animalIdAndDate)
            ->setActionBy($actionBy)
        ;

        $fat1 = (new Fat1())
            ->setFat($values->fat1)
            ->setBodyFat($bodyFat)
            ;
        $fat1
            ->setMeasurementDate($values->measurementDate)
            ->setAnimalIdAndDate($animalIdAndDate)
            ->setActionBy($actionBy)
        ;

        $fat2 = (new Fat2())
            ->setFat($values->fat2)
            ->setBodyFat($bodyFat)
        ;
        $fat2
            ->setMeasurementDate($values->measurementDate)
            ->setAnimalIdAndDate($animalIdAndDate)
        ;

        $fat3 = (new Fat3())
            ->setFat($values->fat3)
            ->setBodyFat($bodyFat)
        ;
        $fat3
            ->setMeasurementDate($values->measurementDate)
            ->setAnimalIdAndDate($animalIdAndDate)
        ;

        $bodyFat->setFat1($fat1);
        $bodyFat->setFat2($fat2);
        $bodyFat->setFat3($fat3);


        $muscleThickness = (new MuscleThickness())
            ->setMuscleThickness($values->muscleThickness)
            ->setScanMeasurementSet($set)
            ->setAnimal($animal)
        ;
        $muscleThickness
            ->setMeasurementDate($values->measurementDate)
            ->setAnimalIdAndDate($animalIdAndDate)
            ->setActionBy($actionBy)
        ;


        $set
            ->setBodyFat($bodyFat)
            ->setMuscleThickness($muscleThickness)
            ->setScanWeight($scanWeight)
            ->setAnimal($animal)
            ->setMeasurementDate($values->measurementDate)
            ->setAnimalIdAndDate($animalIdAndDate)
            ->setActionBy($actionBy)
        ;

        $animal->setScanMeasurementSet($set);

        if ($inspectorByReference) {
            $scanWeight->setInspector($inspectorByReference);
            $bodyFat->setInspector($inspectorByReference);
            $fat1->setInspector($inspectorByReference);
            $fat2->setInspector($inspectorByReference);
            $fat3->setInspector($inspectorByReference);
            $muscleThickness->setInspector($inspectorByReference);
            $set->setInspector($inspectorByReference);
        }

        $this->getManager()->persist($scanWeight);
        $this->getManager()->persist($fat1);
        $this->getManager()->persist($fat2);
        $this->getManager()->persist($fat3);
        $this->getManager()->persist($bodyFat);
        $this->getManager()->persist($muscleThickness);
        $this->getManager()->persist($set);
        $this->getManager()->persist($animal);

        $this->flush();
        $this->getManager()->refresh($set);
        return $set;
    }


    /**
     * @param  ScanMeasurementSet  $set
     * @param  ScanMeasurementsValues  $values
     * @param  Person  $actionBy
     * @return ScanMeasurementSet
     */
    public function edit(ScanMeasurementSet $set, ScanMeasurementsValues $values, Person $actionBy): ScanMeasurementSet
    {
        $animal = $set->getAnimal();
        $editDate = new \DateTime();

        if (!$values->hasSameMeasurementDate($set)) {
            $animalIdAndDate = Measurement::generateAnimalIdAndDate($animal, $values->measurementDate);
            $set
                ->setNestedMeasurementDate($values->measurementDate)
                ->setNestedAnimalIdAndDate($animalIdAndDate)
                ->setNestedActionByAndEditDate($actionBy, $editDate)
            ;
        }

        if (!$values->hasSameInspector($set)) {
            /** @var Inspector|null $inspectorByReference */
            $inspectorByReference = is_int($values->inspectorId) ?
                $this->getManager()->getReference(Inspector::class, intval($values->inspectorId)) : null;

            $set
                ->setNestedInspector($inspectorByReference)
                ->setNestedActionByAndEditDate($actionBy, $editDate)
            ;
        }

        if (!$values->hasSameScanWeight($set)) {
            $set->getScanWeight()->setWeight($values->scanWeight)
                ->setEditDate($editDate)->setActionBy($actionBy);
        }

        if (!$values->hasSameFats($set)) {
            $set->getBodyFat()->getFat1()->setFat($set->getFat1Value())->setEditDate($editDate)->setActionBy($actionBy);
            $set->getBodyFat()->getFat2()->setFat($set->getFat2Value())->setEditDate($editDate)->setActionBy($actionBy);
            $set->getBodyFat()->getFat3()->setFat($set->getFat3Value())->setEditDate($editDate)->setActionBy($actionBy);
        }

        if (!$values->hasSameMuscleThickness($set)) {
            $set->getMuscleThickness()->setMuscleThickness($values->muscleThickness)
                ->setEditDate($editDate)->setActionBy($actionBy);
        }

        $this->getManager()->persist($animal); // has nested cascade persist

        $this->flush();
        $this->getManager()->refresh($set);
        return $set;
    }


    /**
     * @param  ScanMeasurementSet  $set
     * @param  Person  $deletedBy
     */
    public function delete(ScanMeasurementSet $set, Person $deletedBy)
    {
        $animal = $set->getAnimal();
        $deleteDate = new \DateTime();

        $set->nestedDeactivate($deletedBy, $deleteDate);
        $animal->setScanMeasurementSet(null);

        $this->getManager()->persist($animal); // has nested cascade persist

        $this->flush();
    }
}
