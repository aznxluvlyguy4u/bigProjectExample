<?php


namespace AppBundle\JsonFormat;

use JMS\Serializer\Annotation as JMS;

class BreedValueChartDataJsonFormat
{
    /**
     * @var integer
     * @JMS\Type("integer")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS"
     * })
     */
    private $ordinal;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS"
     * })
     */
    private $chartLabel;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS"
     * })
     */
    private $chartGroup;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS"
     * })
     */
    private $chartColor;

    /**
     * @var float
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS"
     * })
     */
    private $value;

    /**
     * @var float
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS"
     * })
     */
    private $accuracy;

    /**
     * @var float
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS"
     * })
     */
    private $normalizedValue;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS"
     * })
     */
    private $exteriorKind;

    /**
     * @return  bool
     * @JMS\Type("bool")
     * @JMS\VirtualProperty
     * @JMS\SerializedName("has_data")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS"
     * })
     */
    public function hasData()
    {
        return $this->value !== null && $this->accuracy !== null && $this->normalizedValue !== null;
    }


    /**
     * @return int
     */
    public function getOrdinal()
    {
        return $this->ordinal;
    }

    /**
     * @param int $ordinal
     * @return BreedValueChartDataJsonFormat
     */
    public function setOrdinal($ordinal)
    {
        $this->ordinal = $ordinal;
        return $this;
    }

    /**
     * @return string
     */
    public function getChartLabel()
    {
        return $this->chartLabel;
    }

    /**
     * @param string $chartLabel
     * @return BreedValueChartDataJsonFormat
     */
    public function setChartLabel($chartLabel)
    {
        $this->chartLabel = $chartLabel;
        return $this;
    }

    /**
     * @return string
     */
    public function getChartGroup()
    {
        return $this->chartGroup;
    }

    /**
     * @param string $chartGroup
     * @return BreedValueChartDataJsonFormat
     */
    public function setChartGroup($chartGroup)
    {
        $this->chartGroup = $chartGroup;
        return $this;
    }

    /**
     * @return string
     */
    public function getChartColor()
    {
        return $this->chartColor;
    }

    /**
     * @param string $chartColor
     * @return BreedValueChartDataJsonFormat
     */
    public function setChartColor($chartColor)
    {
        $this->chartColor = $chartColor;
        return $this;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     * @return BreedValueChartDataJsonFormat
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return float
     */
    public function getAccuracy()
    {
        return $this->accuracy;
    }

    /**
     * @param float $accuracy
     * @return BreedValueChartDataJsonFormat
     */
    public function setAccuracy($accuracy)
    {
        $this->accuracy = $accuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getNormalizedValue()
    {
        return $this->normalizedValue;
    }

    /**
     * @param float $normalizedValue
     * @return BreedValueChartDataJsonFormat
     */
    public function setNormalizedValue($normalizedValue)
    {
        $this->normalizedValue = $normalizedValue;
        return $this;
    }

    /**
     * @return string
     */
    public function getExteriorKind()
    {
        return $this->exteriorKind;
    }

    /**
     * @param string $exteriorKind
     * @return BreedValueChartDataJsonFormat
     */
    public function setExteriorKind($exteriorKind)
    {
        $this->exteriorKind = $exteriorKind;
        return $this;
    }


}