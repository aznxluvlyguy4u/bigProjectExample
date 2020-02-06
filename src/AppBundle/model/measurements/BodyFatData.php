<?php


namespace AppBundle\model\measurements;


use AppBundle\Util\NumberUtil;

class BodyFatData extends MeasurementData implements MeasurementDataInterface
{
    /** @var float */
    public $fat1;

    /** @var float */
    public $fat2;

    /** @var float */
    public $fat3;

    public function __construct(array $sqlResult)
    {
        parent::__construct($sqlResult);

        $this->fat1 = floatval($sqlResult['fat1']);
        $this->fat2 = floatval($sqlResult['fat2']);
        $this->fat3 = floatval($sqlResult['fat3']);
    }

    public function getAnimalIdAndDate(): string
    {
        return $this->animalIdAndDate;
    }

    public function areAllValuesOne(): bool
    {
        $accuracy = 0.0001;
        return NumberUtil::areFloatsEqual($this->fat1, 1.0, $accuracy)
            && NumberUtil::areFloatsEqual($this->fat2, 1.0, $accuracy)
            && NumberUtil::areFloatsEqual($this->fat3, 1.0, $accuracy);
    }
}
