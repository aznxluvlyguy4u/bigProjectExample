<?php


namespace AppBundle\SqlView\View;

use AppBundle\Util\SqlUtil;
use JMS\Serializer\Annotation as JMS;

class ViewScanMeasurements implements SqlViewInterface
{
    /**
     * @var integer
     * @JMS\Type("integer")
     */
    private $animalId;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $logDate;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $measurementDate;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $ddMmYyyyMeasurementDate;

    /**
     * @var boolean
     * @JMS\Type("boolean")
     */
    private $isScanWeightActive;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $weight;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $muscleThickness;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $fat1;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $fat2;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $fat3;

    /**
     * @var integer
     * @JMS\Type("integer")
     */
    private $inspectorId;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $inspector;

    /**
     * @var integer
     * @JMS\Type("integer")
     */
    private $actionById;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $actionBy;

    /**
     * @return string
     */
    static function getPrimaryKeyName()
    {
        return 'animal_id';
    }

    /**
     * @return int
     */
    public function getAnimalId(): int
    {
        return $this->animalId;
    }

    /**
     * @param int $animalId
     * @return ViewScanMeasurements
     */
    public function setAnimalId(int $animalId): ViewScanMeasurements
    {
        $this->animalId = $animalId;
        return $this;
    }

    /**
     * @return string
     */
    public function getLogDate(): string
    {
        return $this->logDate;
    }

    /**
     * @param string $logDate
     * @return ViewScanMeasurements
     */
    public function setLogDate(string $logDate): ViewScanMeasurements
    {
        $this->logDate = $logDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getMeasurementDate(): string
    {
        return $this->measurementDate;
    }

    /**
     * @param string $measurementDate
     * @return ViewScanMeasurements
     */
    public function setMeasurementDate(string $measurementDate): ViewScanMeasurements
    {
        $this->measurementDate = $measurementDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getDdMmYyyyMeasurementDate(): string
    {
        return $this->ddMmYyyyMeasurementDate;
    }

    /**
     * @param string $ddMmYyyyMeasurementDate
     * @return ViewScanMeasurements
     */
    public function setDdMmYyyyMeasurementDate(string $ddMmYyyyMeasurementDate): ViewScanMeasurements
    {
        $this->ddMmYyyyMeasurementDate = $ddMmYyyyMeasurementDate;
        return $this;
    }

    /**
     * @return bool
     */
    public function isScanWeightActive(): bool
    {
        return $this->isScanWeightActive;
    }

    /**
     * @param bool $isScanWeightActive
     * @return ViewScanMeasurements
     */
    public function setIsScanWeightActive(bool $isScanWeightActive): ViewScanMeasurements
    {
        $this->isScanWeightActive = $isScanWeightActive;
        return $this;
    }

    /**
     * @return float
     */
    public function getWeight(): float
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     * @return ViewScanMeasurements
     */
    public function setWeight(float $weight): ViewScanMeasurements
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * @return float
     */
    public function getMuscleThickness(): float
    {
        return $this->muscleThickness;
    }

    /**
     * @param float $muscleThickness
     * @return ViewScanMeasurements
     */
    public function setMuscleThickness(float $muscleThickness): ViewScanMeasurements
    {
        $this->muscleThickness = $muscleThickness;
        return $this;
    }

    /**
     * @return float
     */
    public function getFat1(): float
    {
        return $this->fat1;
    }

    /**
     * @param float $fat1
     * @return ViewScanMeasurements
     */
    public function setFat1(float $fat1): ViewScanMeasurements
    {
        $this->fat1 = $fat1;
        return $this;
    }

    /**
     * @return float
     */
    public function getFat2(): float
    {
        return $this->fat2;
    }

    /**
     * @param float $fat2
     * @return ViewScanMeasurements
     */
    public function setFat2(float $fat2): ViewScanMeasurements
    {
        $this->fat2 = $fat2;
        return $this;
    }

    /**
     * @return float
     */
    public function getFat3(): float
    {
        return $this->fat3;
    }

    /**
     * @param float $fat3
     * @return ViewScanMeasurements
     */
    public function setFat3(float $fat3): ViewScanMeasurements
    {
        $this->fat3 = $fat3;
        return $this;
    }

    /**
     * @return int
     */
    public function getInspectorId(): int
    {
        return $this->inspectorId;
    }

    /**
     * @param int $inspectorId
     * @return ViewScanMeasurements
     */
    public function setInspectorId(int $inspectorId): ViewScanMeasurements
    {
        $this->inspectorId = $inspectorId;
        return $this;
    }

    /**
     * @return string
     */
    public function getInspector(): string
    {
        return $this->inspector;
    }

    /**
     * @param string $inspector
     * @return ViewScanMeasurements
     */
    public function setInspector(string $inspector): ViewScanMeasurements
    {
        $this->inspector = $inspector;
        return $this;
    }

    /**
     * @return int
     */
    public function getActionById(): int
    {
        return $this->actionById;
    }

    /**
     * @param int $actionById
     * @return ViewScanMeasurements
     */
    public function setActionById(int $actionById): ViewScanMeasurements
    {
        $this->actionById = $actionById;
        return $this;
    }

    /**
     * @return string
     */
    public function getActionBy(): string
    {
        return $this->actionBy;
    }

    /**
     * @param string $actionBy
     * @return ViewScanMeasurements
     */
    public function setActionBy(string $actionBy): ViewScanMeasurements
    {
        $this->actionBy = $actionBy;
        return $this;
    }


}
