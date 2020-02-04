<?php


namespace AppBundle\model\measurements;


class MuscleThicknessData extends MeasurementData implements MeasurementDataInterface
{
    /** @var float */
    public $muscleThickness;

    public function __construct(array $sqlResult)
    {
        parent::__construct($sqlResult);

        $this->muscleThickness = floatval($sqlResult['muscle_thickness']);
    }

    public function getAnimalIdAndDate(): string
    {
        return $this->animalIdAndDate;
    }
}
