<?php

namespace AppBundle\Output\BirthMeasurements;


use AppBundle\Entity\Weight;
use JMS\Serializer\Annotation as JMS;

/**
 * Class BirthWeightOutput
 * @package AppBundle\Output\BirthMeasurements
 */
class BirthWeightOutput
{
    /**
     * @var float
     * @JMS\Type("float")
     */
    private $weight = null;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @var \DateTime
     * @JMS\Type("DateTime")
     */
    private $measurementDate;

    /**
     * BirthWeightOutput constructor.
     * @param Weight|null $birthWeight
     */
    public function __construct(?Weight $birthWeight = null)
    {
        if ($birthWeight) {
            $this->weight = $birthWeight->getWeight();
            $this->measurementDate = $birthWeight->getMeasurementDate();
        }
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
     * @return BirthWeightOutput
     */
    public function setWeight(float $weight): BirthWeightOutput
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getMeasurementDate(): \DateTime
    {
        return $this->measurementDate;
    }

    /**
     * @param \DateTime $measurementDate
     * @return BirthWeightOutput
     */
    public function setMeasurementDate(\DateTime $measurementDate): BirthWeightOutput
    {
        $this->measurementDate = $measurementDate;
        return $this;
    }


}