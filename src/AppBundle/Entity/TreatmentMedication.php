<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class TreatmentMedication
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\TreatmentMedicationRepository")
 * @package AppBundle\Entity
 */
class TreatmentMedication
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT"
     * })
     */
    private $id;

    /**
     * @var string
     * @JMS\Type("string")
     * @Assert\NotBlank
     * @ORM\Column(type="string")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT_TEMPLATE_MIN",
     *     "TREATMENT"
     * })
     */
    private $name;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT"
     * })
     * @Assert\NotBlank
     */
    private $dosage;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT"
     * })
     * @Assert\NotBlank
     */
    private $dosageUnit;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT"
     * })
     */
    private $regNl;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=false)
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT"
     * })
     */
    private $treatmentDuration;

    /**
     * @ORM\Column(type="integer", nullable=false)
     * @JMS\Type("integer")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT"
     * })
     */
    private $waitingDays;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default":true})
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT"
     * })
     */
    private $isActive = true;

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\TreatmentTemplate", mappedBy="treatmentMedications", cascade={"persist", "remove"})
     * @JMS\Type("ArrayCollection<AppBundle\Entity\TreatmentTemplate>")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT"
     * })
     */
    private $treatmentTemplates;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return TreatmentMedication
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @param bool $isActive
     * @return TreatmentMedication
     */
    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return float
     */
    public function getDosage(): float
    {
        return $this->dosage;
    }

    /**
     * @param float $dosage
     * @return TreatmentMedication
     */
    public function setDosage(float $dosage): self
    {
        $this->dosage = $dosage;

        return $this;
    }

    /**
     * @return string
     */
    public function getDosageUnit(): string
    {
        return $this->dosageUnit;
    }

    /**
     * @param string $dosageUnit
     * @return TreatmentMedication
     */
    public function setDosageUnit(string $dosageUnit): self
    {
        $this->dosageUnit = $dosageUnit;

        return $this;
    }

    /**
     * @return string
     */
    public function getRegNl(): string
    {
        return $this->regNl;
    }

    /**
     * @param string $regNl
     * @return TreatmentMedication
     */
    public function setRegNl(string $regNl): self
    {
        $this->regNl = $regNl;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getTreatmentDuration(): ?float
    {
        return $this->treatmentDuration;
    }

    /**
     * @param float|null $treatmentDuration
     * @return TreatmentMedication
     */
    public function setTreatmentDuration(?float $treatmentDuration): self
    {
        $this->treatmentDuration = $treatmentDuration;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWaitingDays()
    {
        return $this->waitingDays;
    }

    /**
     * @param mixed $waitingDays
     * @return TreatmentMedication
     */
    public function setWaitingDays($waitingDays): self
    {
        $this->waitingDays = $waitingDays;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getTreatmentTemplates(): ArrayCollection
    {
        return $this->treatmentTemplates;
    }

    /**
     * @param TreatmentTemplate $treatmentTemplate
     * @return TreatmentMedication
     */
    public function addTreatmentTemplate(TreatmentTemplate $treatmentTemplate): self
    {
        $this->treatmentTemplates->add($treatmentTemplate);

        return $this;
    }

}
