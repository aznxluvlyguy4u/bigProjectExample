<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class MedicationSelection
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\MedicationSelectionRepository")
 * @package AppBundle\Entity
 */
class MedicationSelection
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Groups({
     *     "TREATMENT_MIN"
     * })
     */
    private $id;

    /**
     * @var Treatment
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Treatment", inversedBy="medicationSelections")
     * @ORM\JoinColumn(name="treatment_id", referencedColumnName="id", onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Treatment")
     */
    private $treatment;

    /**
     * @var TreatmentMedication
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\TreatmentMedication", inversedBy="medicationSelections")
     * @JMS\Type("AppBundle\Entity\TreatmentMedication")
     *
     * @JMS\Groups({
     *     "TREATMENT",
     *     "TREATMENT_MIN"
     * })
     */
    private $treatmentMedication;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "TREATMENT",
     *     "TREATMENT_MIN"
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
     *     "TREATMENT",
     *     "TREATMENT_MIN"
     * })
     * @Assert\NotBlank
     * @Assert\Regex("/aantal|mg|ml|g|l/m")
     */
    private $dosageUnit;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     * @JMS\Groups({
     *     "TREATMENT",
     *     "TREATMENT_MIN"
     * })
     */
    private $waitingDays;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "TREATMENT",
     *     "TREATMENT_MIN"
     * })
     */
    private $regNl;

    //don't remove this because when you try to retrieve the entity there will be an error.
    //TODO: fix "Can not find property 'description' of MedicationSelection" error when retrieving the entity
    private $description;

    /**
     * MedicationSelection constructor.
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
     * @return MedicationSelection
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Treatment
     */
    public function getTreatment()
    {
        return $this->treatment;
    }

    /**
     * @param Treatment $treatment
     * @return MedicationSelection
     */
    public function setTreatment($treatment)
    {
        $this->treatment = $treatment;
        return $this;
    }

    /**
     * @return TreatmentMedication
     */
    public function getTreatmentMedication()
    {
        return $this->treatmentMedication;
    }

    /**
     * @param TreatmentMedication $treatmentMedication
     * @return MedicationSelection
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
     * @return MedicationSelection
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
     * @return MedicationSelection
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
     * @return MedicationSelection
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
     * @return MedicationSelection
     */
    public function setRegNl(?string $regNl): self
    {
        $this->regNl = $regNl;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return MedicationSelection
     */
    public function setDescription($description): self
    {
        $this->description = $description;
        return $this;
    }
}
