<?php


namespace AppBundle\model\measurements;


class WeightData extends MeasurementData implements MeasurementDataInterface
{
    /** @var float */
    public $weight;

    /** @var bool */
    public $isBirthWeight;

    public function __construct(array $sqlResult)
    {
        parent::__construct($sqlResult);

        $this->weight = floatval($sqlResult['weight']);
        $this->isBirthWeight = floatval($sqlResult['is_birth_weight']);
    }

    public function getAnimalIdAndDate(): string
    {
        return $this->animalIdAndDate;
    }
}
