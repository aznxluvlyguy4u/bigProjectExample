<?php

namespace AppBundle\Output\BirthMeasurements;


use JMS\Serializer\Annotation as JMS;

/**
 * Class BirthMeasurementsOutput
 * @package AppBundle\Output\BirthMeasurements
 */
class BirthMeasurementsOutput
{
    /**
     * @var TailLengthOutput|null
     * @JMS\Type("AppBundle\Output\BirthMeasurements\TailLengthOutput")
     */
    private $tailLength = null;

    /**
     * @var BirthWeightOutput|null
     * @JMS\Type("AppBundle\Output\BirthMeasurements\BirthWeightOutput")
     */
    private $birthWeight = null;

    /**
     * @var string|null
     * @JMS\Type("string")
     */
    private $birthProgress = null;

    /**
     * @return TailLengthOutput|null
     */
    public function getTailLength(): ?TailLengthOutput
    {
        return $this->tailLength;
    }

    /**
     * @param TailLengthOutput|null $tailLength
     * @return BirthMeasurementsOutput
     */
    public function setTailLength(?TailLengthOutput $tailLength): BirthMeasurementsOutput
    {
        $this->tailLength = $tailLength;
        return $this;
    }

    /**
     * @return BirthWeightOutput|null
     */
    public function getBirthWeight(): ?BirthWeightOutput
    {
        return $this->birthWeight;
    }

    /**
     * @param BirthWeightOutput|null $birthWeight
     * @return BirthMeasurementsOutput
     */
    public function setBirthWeight(?BirthWeightOutput $birthWeight): BirthMeasurementsOutput
    {
        $this->birthWeight = $birthWeight;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getBirthProgress(): ?string
    {
        return $this->birthProgress;
    }

    /**
     * @param  string|null  $birthProgress
     * @return BirthMeasurementsOutput
     */
    public function setBirthProgress(?string $birthProgress): BirthMeasurementsOutput
    {
        $this->birthProgress = $birthProgress;
        return $this;
    }



}
