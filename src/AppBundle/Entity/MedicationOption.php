<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class MedicationOption
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\MedicationOptionRepository")
 * @package AppBundle\Entity
 */
class MedicationOption
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE"
     * })
     */
    private $id;

    /**
     * @var TreatmentTemplate
     * @ORM\ManyToOne(targetEntity="TreatmentTemplate", inversedBy="medications")
     * @ORM\JoinColumn(name="treatment_template_id", referencedColumnName="id", onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\TreatmentTemplate")
     */
    private $treatmentTemplate;

    /**
     * @var TreatmentMedication
     * @ORM\ManyToOne(targetEntity="TreatmentMedication", inversedBy="medications")
     * @JMS\Type("AppBundle\Entity\TreatmentMedication")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT_TEMPLATE_MIN"
     * })
     */
    private $treatmentMedication;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT_TEMPLATE_MIN"
     * })
     * @Assert\NotBlank
     * @Assert\Regex("/aantal|mg|ml|g|l/m")
     */
    private $dosage;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT_TEMPLATE_MIN"
     * })
     * @Assert\NotBlank
     */
    private $dosageUnit;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT_TEMPLATE_MIN"
     * })
     */
    private $waitingDays;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT_TEMPLATE_MIN"
     * })
     */
    private $regNl;

    //don't remove this because when you try to retrieve the entity there will be an error.
    private $description;

    /**
     * MedicationOption constructor.
     */
    public function __construct()
    {
        $this->dosage = 0.0;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return MedicationOption
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return TreatmentTemplate
     */
    public function getTreatmentTemplate()
    {
        return $this->treatmentTemplate;
    }

    /**
     * @param TreatmentTemplate $treatmentTemplate
     * @return MedicationOption
     */
    public function setTreatmentTemplate($treatmentTemplate)
    {
        $this->treatmentTemplate = $treatmentTemplate;
        return $this;
    }

    /**
     * @return TreatmentMedication
     *
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT_TEMPLATE_MIN"
     * })
     */
    public function getTreatmentMedication()
    {
        return $this->treatmentMedication;
    }

    /**
     * @param TreatmentMedication $treatmentMedication
     * @return MedicationOption
     */
    public function setTreatmentMedication($treatmentMedication)
    {
        $this->treatmentMedication = $treatmentMedication;
        return $this;
    }

    /**
     * @return float
     */
    public function getDosage()
    {
        return $this->dosage;
    }

    /**
     * @param float $dosage
     * @return MedicationOption
     */
    public function setDosage($dosage)
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
     * @return MedicationOption
     */
    public function setDosageUnit(string $dosageUnit): self
    {
        $this->dosageUnit = $dosageUnit;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getWaitingDays(): ?int
    {
        return $this->waitingDays;
    }

    /**
     * @param int $waitingDays
     * @return MedicationOption
     */
    public function setWaitingDays(int $waitingDays): self
    {
        $this->waitingDays = $waitingDays;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRegNl(): ?string
    {
        return $this->regNl;
    }

    /**
     * @param string|null $regNl
     * @return MedicationOption
     */
    public function setRegNl(?string $regNl): self
    {
        $this->regNl = $regNl;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }
}
