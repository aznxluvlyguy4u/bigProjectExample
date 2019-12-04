<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ScanMeasurementSet
 * @ORM\Entity(repositoryClass="ScanMeasurementSetRepository")
 * @package AppBundle\Entity
 */
class ScanMeasurementSet extends Measurement
{
    use EntityClassInfo;

    /**
     * @ORM\ManyToOne(targetEntity="Animal", cascade={"persist"})
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    /**
     * @var Weight
     *
     * @ORM\OneToOne(targetEntity="Weight", inversedBy="scanMeasurementSet", cascade={"persist"})
     * @ORM\JoinColumn(name="scan_weight_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Weight")
     */
    private $scanWeight;

    /**
     * @var BodyFat
     *
     * @ORM\OneToOne(targetEntity="BodyFat", cascade={"persist"})
     * @ORM\JoinColumn(name="body_fat_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BodyFat")
     */
    private $bodyFat;

    /**
     * @var MuscleThickness
     *
     * @ORM\OneToOne(targetEntity="MuscleThickness", cascade={"persist"})
     * @ORM\JoinColumn(name="muscle_thickness_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\MuscleThickness")
     */
    private $muscleThickness;

    /**
     * @return Weight
     */
    public function getScanWeight(): Weight
    {
        return $this->scanWeight;
    }

    /**
     * @param Weight $scanWeight
     * @return ScanMeasurementSet
     */
    public function setScanWeight(Weight $scanWeight): ScanMeasurementSet
    {
        $this->scanWeight = $scanWeight;
        return $this;
    }

    /**
     * @return BodyFat
     */
    public function getBodyFat(): BodyFat
    {
        return $this->bodyFat;
    }

    /**
     * @param BodyFat $bodyFat
     * @return ScanMeasurementSet
     */
    public function setBodyFat(BodyFat $bodyFat): ScanMeasurementSet
    {
        $this->bodyFat = $bodyFat;
        return $this;
    }

    /**
     * @return MuscleThickness
     */
    public function getMuscleThickness(): MuscleThickness
    {
        return $this->muscleThickness;
    }

    /**
     * @param MuscleThickness $muscleThickness
     * @return ScanMeasurementSet
     */
    public function setMuscleThickness(MuscleThickness $muscleThickness): ScanMeasurementSet
    {
        $this->muscleThickness = $muscleThickness;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * @param mixed $animal
     * @return ScanMeasurementSet
     */
    public function setAnimal($animal)
    {
        $this->animal = $animal;
        return $this;
    }


}
