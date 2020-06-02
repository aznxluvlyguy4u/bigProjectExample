<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use AppBundle\Util\Translation;
use DateTime;
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
     *     "TREATMENT_TEMPLATE_MIN",
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
     * @var boolean
     * @ORM\Column(type="boolean", options={"default":true})
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT_TEMPLATE_MIN",
     *     "TREATMENT"
     * })
     */
    private $isActive = true;

//    /**
//     * @var ArrayCollection|MedicationOption[]
//     * @JMS\Type("ArrayCollection<AppBundle\Entity\MedicationOption>")
//     * @ORM\OneToMany(targetEntity="AppBundle\Entity\MedicationOption", mappedBy="treatmentMedication")
//     */
//    private $medicationOptions;
//
//    /**
//     * @var ArrayCollection|MedicationSelection[]
//     * @JMS\Type("ArrayCollection<AppBundle\Entity\MedicationSelection>")
//     * @ORM\OneToMany(targetEntity="AppBundle\Entity\MedicationSelection", mappedBy="treatmentMedication")
//     */
//    private $medicationSelections;

//    public function __construct()
//    {
//        $this->medicationOptions = new ArrayCollection();
//        $this->medicationSelections = new ArrayCollection();
//    }

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

//    /**
//     * @return ArrayCollection|MedicationOption[]
//     */
//    public function getMedicationOptions(): ?ArrayCollection
//    {
//        return $this->medicationOptions;
//    }
//
//    /**
//     * @param MedicationOption $medicationOption
//     * @return TreatmentMedication
//     */
//    public function addMedicationOption(MedicationOption $medicationOption): self
//    {
//        $this->medicationOptions->add($medicationOption);
//        return $this;
//    }
//
//    /**
//     * @param MedicationOption $medicationOption
//     * @return TreatmentMedication
//     */
//    public function removeMedicationOption(MedicationOption $medicationOption): self
//    {
//        $this->medicationOptions->removeElement($medicationOption);
//        return $this;
//    }
//
//    /**
//     * @return ArrayCollection|MedicationSelection[]
//     */
//    public function getMedicationSelections(): ArrayCollection
//    {
//        return $this->medicationSelections;
//    }
//
//    /**
//     * @param MedicationSelection $medicationSelection
//     * @return TreatmentMedication
//     */
//    public function addMedicationSelection(MedicationSelection $medicationSelection): self
//    {
//        $this->medicationSelections->add($medicationSelection);
//        return $this;
//    }
//
//    /**
//     * @param MedicationSelection $medicationSelection
//     * @return TreatmentMedication
//     */
//    public function removeMedicationSelection(MedicationSelection $medicationSelection): self
//    {
//        $this->medicationSelections->removeElement($medicationSelection);
//        return $this;
//    }
}