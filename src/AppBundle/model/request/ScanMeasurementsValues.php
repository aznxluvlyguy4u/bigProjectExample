<?php


namespace AppBundle\model\request;


use AppBundle\Entity\ScanMeasurementSet;
use AppBundle\Util\DateUtil;
use AppBundle\Util\NumberUtil;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;
use AppBundle\Validator\Constraints as CustomAssert;

/**
 * Class ScanMeasurementsRequest
 * @package AppBundle\JsonFormat
 */
class ScanMeasurementsValues
{
    /**
     * @var float
     * @Assert\GreaterThanOrEqual(0)
     * @Assert\LessThanOrEqual(9)
     * @Assert\NotNull
     * @Assert\Type("float")
     * @CustomAssert\LessThanOrEqualDecimalCount(1)
     * @JMS\Type("float")
     */
    public $fat1;

    /**
     * @var float
     * @Assert\GreaterThanOrEqual(0)
     * @Assert\LessThanOrEqual(9)
     * @Assert\NotNull
     * @Assert\Type("float")NSFO STAGE m4 Large  on NSFO AWS account
     * @CustomAssert\LessThanOrEqualDecimalCount(1)
     * @JMS\Type("float")
     */
    public $fat2;

    /**
     * @var float
     * @Assert\GreaterThanOrEqual(0)
     * @Assert\LessThanOrEqual(9)
     * @Assert\NotNull
     * @Assert\Type("float")
     * @CustomAssert\LessThanOrEqualDecimalCount(1)
     * @JMS\Type("float")
     */
    public $fat3;

    /**
     * @var float
     * @Assert\GreaterThanOrEqual(10)
     * @Assert\LessThanOrEqual(50)
     * @Assert\NotNull
     * @Assert\Type("float")
     * @CustomAssert\LessThanOrEqualDecimalCount(1)
     * @JMS\Type("float")
     */
    public $muscleThickness;

    /**
     * @var float
     * @Assert\GreaterThanOrEqual(10)
     * @Assert\LessThanOrEqual(99)
     * @Assert\NotNull
     * @Assert\Type("float")
     * @CustomAssert\LessThanOrEqualDecimalCount(2)
     * @JMS\Type("float")
     */
    public $scanWeight;

    /**
     * @var \DateTime
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    public $measurementDate;

    /**
     * @var int
     * @Assert\Type("integer")
     * @JMS\Type("integer")
     */
    public $inspectorId;

    public function mapScanMeasurementSet(ScanMeasurementSet $set): ScanMeasurementsValues
    {
        $this->fat1 = $set->getFat1Value();
        $this->fat2 = $set->getFat2Value();
        $this->fat3 = $set->getFat3Value();
        $this->muscleThickness = $set->getMuscleThicknessValue();
        $this->scanWeight = $set->getScanWeightValue();
        return $this;
    }

    public function hasEqualValues(ScanMeasurementSet $set): bool
    {
        return $this->hasSameFats($set) &&
            $this->hasSameScanWeight($set) &&
            $this->hasSameMuscleThickness($set) &&
            $this->hasSameMeasurementDate($set) &&
            $this->hasSameInspector($set)
        ;
    }

    public function hasSameFats(ScanMeasurementSet $set): bool
    {
        return $this->hasSameFat1($set) &&
            $this->hasSameFat2($set) &&
            $this->hasSameFat3($set)
        ;
    }

    public function hasSameFat1(ScanMeasurementSet $set): bool
    {
        return NumberUtil::areFloatsEqual($this->fat1, $set->getFat1Value());
    }

    public function hasSameFat2(ScanMeasurementSet $set): bool
    {
        return NumberUtil::areFloatsEqual($this->fat2, $set->getFat2Value());
    }

    public function hasSameFat3(ScanMeasurementSet $set): bool
    {
        return NumberUtil::areFloatsEqual($this->fat3, $set->getFat3Value());
    }

    public function hasSameScanWeight(ScanMeasurementSet $set): bool
    {
        return NumberUtil::areFloatsEqual($this->scanWeight, $set->getScanWeightValue());
    }

    public function hasSameMuscleThickness(ScanMeasurementSet $set): bool
    {
        return NumberUtil::areFloatsEqual($this->muscleThickness, $set->getMuscleThicknessValue());
    }

    public function hasSameMeasurementDate(ScanMeasurementSet $set): bool
    {
        return DateUtil::hasSameDateIgnoringTimezoneAndTimeZoneType($set->getMeasurementDate(), $this->measurementDate);
    }

    public function hasSameInspector(ScanMeasurementSet $set): bool
    {
        return $set->getInspectorId() === $this->inspectorId;
    }
}
