<?php


namespace AppBundle\model\measurements;


abstract class MeasurementData
{
    /** @var int */
    public $id;
    /** @var int */
    public $animalId;
    /** @var \DateTime */
    public $logDate;
    /** @var \DateTime */
    public $measurementDate;
    /** @var string */
    public $animalIdAndDate;

    /** @var int|null */
    public $inspectorId;

    /** @var int */
    public $priorityLevel = 1;

    /**
     * MeasurementData constructor.
     * @param array $sqlResult
     */
    public function __construct(array $sqlResult)
    {
        $this->id = $sqlResult['id'];
        $this->animalId = $sqlResult['animal_id'];
        $this->logDate = new \DateTime($sqlResult['log_date']);
        $this->measurementDate = new \DateTime($sqlResult['measurement_date']);
        $this->animalIdAndDate = $sqlResult['animal_id_and_date'];
        $this->inspectorId = $sqlResult['inspector_id'];
    }

    public function hasInspector(): bool
    {
        return $this->inspectorId != null;
    }
}
