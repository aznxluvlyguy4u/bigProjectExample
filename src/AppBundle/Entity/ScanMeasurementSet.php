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
     * @var Animal
     * @ORM\ManyToOne(targetEntity="Animal", cascade={"persist"})
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    /**
     * @var Weight
     *
     * @ORM\OneToOne(targetEntity="Weight", inversedBy="scanMeasurementSet", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(name="scan_weight_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Weight")
     */
    private $scanWeight;

    /**
     * @var BodyFat
     *
     * @ORM\OneToOne(targetEntity="BodyFat", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(name="body_fat_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BodyFat")
     */
    private $bodyFat;

    /**
     * @var MuscleThickness
     *
     * @ORM\OneToOne(targetEntity="MuscleThickness", cascade={"persist"}, fetch="EAGER")
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
     * @return Animal
     */
    public function getAnimal(): Animal
    {
        return $this->animal;
    }

    /**
     * @param Animal $animal
     * @return ScanMeasurementSet
     */
    public function setAnimal($animal)
    {
        $this->animal = $animal;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getFat1Value(): ?float
    {
        if ($this->getBodyFat()) {
            return $this->getBodyFat()->getFat1() ? $this->getBodyFat()->getFat1()->getFat() : null;
        }
        return null;
    }

    /**
     * @return float|null
     */
    public function getFat2Value(): ?float
    {
        if ($this->getBodyFat()) {
            return $this->getBodyFat()->getFat2() ? $this->getBodyFat()->getFat2()->getFat() : null;
        }
        return null;
    }

    /**
     * @return float|null
     */
    public function getFat3Value(): ?float
    {
        if ($this->getBodyFat()) {
            return $this->getBodyFat()->getFat3() ? $this->getBodyFat()->getFat3()->getFat() : null;
        }
        return null;
    }

    /**
     * @return float|null
     */
    public function getScanWeightValue(): ?float
    {
        return $this->getScanWeight() ? $this->getScanWeight()->getWeight() : null;
    }

    /**
     * @return float|null
     */
    public function getMuscleThicknessValue(): ?float
    {
        return $this->getMuscleThickness() ? $this->getMuscleThickness()->getMuscleThickness() : null;
    }

    /**
     * Set measurementDate
     *
     * @param \DateTime $measurementDate
     *
     * @return ScanMeasurementSet
     */
    public function setNestedMeasurementDate($measurementDate): ScanMeasurementSet
    {
        $this->measurementDate = $measurementDate;

        if ($this->bodyFat) {
            $this->bodyFat->setMeasurementDate($measurementDate);
            $this->bodyFat->getFat1()->setMeasurementDate($measurementDate);
            $this->bodyFat->getFat2()->setMeasurementDate($measurementDate);
            $this->bodyFat->getFat3()->setMeasurementDate($measurementDate);
        }

        if ($this->scanWeight) {
            $this->scanWeight->setMeasurementDate($measurementDate);
        }

        if ($this->muscleThickness) {
            $this->muscleThickness->setMeasurementDate($measurementDate);
        }

        return $this;
    }


    /**
     * Set animalIdAndDate
     *
     * @param string $animalIdAndDate
     *
     * @return ScanMeasurementSet
     */
    public function setNestedAnimalIdAndDate($animalIdAndDate): ScanMeasurementSet
    {
        $this->animalIdAndDate = $animalIdAndDate;

        if ($this->bodyFat) {
            $this->bodyFat->setAnimalIdAndDate($animalIdAndDate);
            $this->bodyFat->getFat1()->setAnimalIdAndDate($animalIdAndDate);
            $this->bodyFat->getFat2()->setAnimalIdAndDate($animalIdAndDate);
            $this->bodyFat->getFat3()->setAnimalIdAndDate($animalIdAndDate);
        }

        if ($this->scanWeight) {
            $this->scanWeight->setAnimalIdAndDate($animalIdAndDate);
        }

        if ($this->muscleThickness) {
            $this->muscleThickness->setAnimalIdAndDate($animalIdAndDate);
        }

        return $this;
    }


    /**
     * Set $inspector
     *
     * @param Inspector|null $inspector
     *
     * @return ScanMeasurementSet
     */
    public function setNestedInspector(?Inspector $inspector): ScanMeasurementSet
    {
        $this->setInspector($inspector);

        if ($this->bodyFat) {
            $this->bodyFat->setInspector($inspector);
            $this->bodyFat->getFat1()->setInspector($inspector);
            $this->bodyFat->getFat2()->setInspector($inspector);
            $this->bodyFat->getFat3()->setInspector($inspector);
        }

        if ($this->scanWeight) {
            $this->scanWeight->setInspector($inspector);
        }

        if ($this->muscleThickness) {
            $this->muscleThickness->setInspector($inspector);
        }

        return $this;
    }


    /**
     * Set nested actionBy and editDate
     *
     * @param Person $actionBy
     * @param \DateTime $editDate
     *
     * @return ScanMeasurementSet
     */
    public function setNestedActionByAndEditDate(Person $actionBy, \DateTime $editDate): ScanMeasurementSet
    {
        $this->setActionBy($actionBy);
        $this->setEditDate($editDate);

        if ($this->bodyFat) {
            $this->bodyFat->setActionBy($actionBy);
            $this->bodyFat->getFat1()->setActionBy($actionBy);
            $this->bodyFat->getFat2()->setActionBy($actionBy);
            $this->bodyFat->getFat3()->setActionBy($actionBy);
            $this->bodyFat->setEditDate($editDate);
            $this->bodyFat->getFat1()->setEditDate($editDate);
            $this->bodyFat->getFat2()->setEditDate($editDate);
            $this->bodyFat->getFat3()->setEditDate($editDate);
        }

        if ($this->scanWeight) {
            $this->scanWeight->setActionBy($actionBy);
            $this->scanWeight->setEditDate($editDate);
        }

        if ($this->muscleThickness) {
            $this->muscleThickness->setActionBy($actionBy);
            $this->muscleThickness->setEditDate($editDate);
        }

        return $this;
    }


    /**
     * Set nested actionBy and editDate
     *
     * @param Person $deletedBy
     * @param \DateTime $deleteDate
     *
     * @return ScanMeasurementSet
     */
    public function nestedDeactivate(Person $deletedBy, \DateTime $deleteDate): ScanMeasurementSet
    {
        $this->setDeletedBy($deletedBy);
        $this->setDeleteDate($deleteDate);
        $this->setIsActive(false);

        if ($this->bodyFat) {
            $this->bodyFat->setDeletedBy($deletedBy);
            $this->bodyFat->getFat1()->setDeletedBy($deletedBy);
            $this->bodyFat->getFat2()->setDeletedBy($deletedBy);
            $this->bodyFat->getFat3()->setDeletedBy($deletedBy);
            $this->bodyFat->setDeleteDate($deleteDate);
            $this->bodyFat->getFat1()->setDeleteDate($deleteDate);
            $this->bodyFat->getFat2()->setDeleteDate($deleteDate);
            $this->bodyFat->getFat3()->setDeleteDate($deleteDate);
            $this->bodyFat->setIsActive(false);
            $this->bodyFat->getFat1()->setIsActive(false);
            $this->bodyFat->getFat2()->setIsActive(false);
            $this->bodyFat->getFat3()->setIsActive(false);
        }

        if ($this->scanWeight) {
            $this->scanWeight->setDeletedBy($deletedBy);
            $this->scanWeight->setDeleteDate($deleteDate);
            $this->scanWeight->setIsActive(false);
        }

        if ($this->muscleThickness) {
            $this->muscleThickness->setDeletedBy($deletedBy);
            $this->muscleThickness->setDeleteDate($deleteDate);
            $this->muscleThickness->setIsActive(false);
        }

        return $this;
    }
}
