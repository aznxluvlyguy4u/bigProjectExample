<?php

namespace AppBundle\Output\BirthMeasurements;


use AppBundle\Entity\TailLength;
use JMS\Serializer\Annotation as JMS;

/**
 * Class TailLengthOutput
 * @package AppBundle\Output\BirthMeasurements
 */
class TailLengthOutput
{
    /**
     * @var float
     * @JMS\Type("float")
     */
    private $length = null;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @var \DateTime
     * @JMS\Type("DateTime")
     */
    private $measurementDate;

    /**
     * TailLengthOutput constructor.
     * @param TailLength|null $tailLength
     */
    public function __construct(?TailLength $tailLength = null)
    {
        if ($tailLength) {
            $this->length = $tailLength->getLength();
            $this->measurementDate = $tailLength->getMeasurementDate();
        }
    }


    /**
     * @return float
     */
    public function getLength(): float
    {
        return $this->length;
    }

    /**
     * @param float $length
     * @return TailLengthOutput
     */
    public function setLength(float $length): TailLengthOutput
    {
        $this->length = $length;
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
     * @return TailLengthOutput
     */
    public function setMeasurementDate(\DateTime $measurementDate): TailLengthOutput
    {
        $this->measurementDate = $measurementDate;
        return $this;
    }


}