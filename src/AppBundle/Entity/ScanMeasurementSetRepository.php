<?php

namespace AppBundle\Entity;

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
}