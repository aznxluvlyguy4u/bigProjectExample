<?php


namespace AppBundle\model;


class ScanMeasurementsUnlinkedData
{
    /** @var int */
    public $animalId;
    /** @var \DateTime */
    public $measurementDate;
    /** @var string */
    public $animalIdAndDate;
    /** @var int */
    public $scanWeightId;
    /** @var int */
    public $bodyFatId;
    /** @var int */
    public $muscleThicknessId;
    /** @var int|null */
    public $scanInspectorId;
    /** @var int|null */
    public $scanWeightInspectorId;
    /** @var int|null */
    public $bodyFatInspectorId;
    /** @var int|null */
    public $muscleThicknessInspectorId;

    /**
     * ScanMeasurementsUnlinkedData constructor.
     * @param array $sqlResult
     */
    public function __construct(array $sqlResult)
    {
        $this->animalId = $sqlResult['animal_id'];
        $this->measurementDate = new \DateTime($sqlResult['measurement_date']);
        $this->animalIdAndDate = $sqlResult['animal_id_and_date'];
        $this->scanWeightId = $sqlResult['scan_weight_id'];
        $this->bodyFatId = $sqlResult['body_fat_id'];
        $this->muscleThicknessId = $sqlResult['muscle_thickness_id'];
        $this->scanInspectorId = $sqlResult['scan_inspector_id'];
        $this->scanWeightInspectorId = $sqlResult['scan_weight_inspector_id'];
        $this->bodyFatInspectorId = $sqlResult['body_fat_inspector_id'];
        $this->muscleThicknessInspectorId = $sqlResult['muscle_thickness_inspector_id'];
    }
}