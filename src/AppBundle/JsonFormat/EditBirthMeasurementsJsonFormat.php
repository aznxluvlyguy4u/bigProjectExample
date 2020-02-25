<?php

namespace AppBundle\JsonFormat;


/**
 * Class EditBirthMeasurementsJsonFormat
 * @package AppBundle\JsonFormat
 */
class EditBirthMeasurementsJsonFormat
{
    /**
     * @var float|null
     */
    private $tailLength = null;

    /**
     * @var float|null
     */
    private $birthWeight = null;

    /**
     * @var string|null
     */
    private $birthProgress = null;

    /**
     * @var boolean
     */
    private $resetMeasurementDateUsingDateOfBirth = false;

    /**
     * @return float|null
     */
    public function getTailLength(): ?float
    {
        return $this->tailLength;
    }

    /**
     * @param float|null $tailLength
     * @return EditBirthMeasurementsJsonFormat
     */
    public function setTailLength(?float $tailLength): EditBirthMeasurementsJsonFormat
    {
        $this->tailLength = $tailLength;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getBirthWeight(): ?float
    {
        return $this->birthWeight;
    }

    /**
     * @param float|null $birthWeight
     * @return EditBirthMeasurementsJsonFormat
     */
    public function setBirthWeight(?float $birthWeight): EditBirthMeasurementsJsonFormat
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
     * @return EditBirthMeasurementsJsonFormat
     */
    public function setBirthProgress(?string $birthProgress): EditBirthMeasurementsJsonFormat
    {
        $this->birthProgress = $birthProgress;
        return $this;
    }

    /**
     * @return bool
     */
    public function isResetMeasurementDateUsingDateOfBirth(): bool
    {
        return $this->resetMeasurementDateUsingDateOfBirth;
    }

    /**
     * @param bool $resetMeasurementDateUsingDateOfBirth
     * @return EditBirthMeasurementsJsonFormat
     */
    public function setResetMeasurementDateUsingDateOfBirth(bool $resetMeasurementDateUsingDateOfBirth): EditBirthMeasurementsJsonFormat
    {
        $this->resetMeasurementDateUsingDateOfBirth = $resetMeasurementDateUsingDateOfBirth;
        return $this;
    }



}
