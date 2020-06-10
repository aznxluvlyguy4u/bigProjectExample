<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use DateTime;
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
     *     "TREATMENT",
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
     * @var MedicationOption
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\MedicationOption")
     * @ORM\JoinColumn(name="medication_option_id", referencedColumnName="id", onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\MedicationOption")
     */
    private $medicationOption;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @JMS\Type("datetime")
     * @JMS\Groups({
     *     "TREATMENT",
     *     "TREATMENT_MIN"
     * })
     */
    private $waitingTimeEnd;

    //don't remove this because when you try to retrieve the entity there will be an error.
    //TODO: fix "Can not find property 'description' of MedicationSelection" error when retrieving the entity
    private $description;

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
     * @return MedicationOption
     */
    public function getMedicationOption(): MedicationOption
    {
        return $this->medicationOption;
    }

    /**
     * @param MedicationOption $medicationOption
     * @return MedicationSelection
     */
    public function setMedicationOption(MedicationOption $medicationOption): self
    {
        $this->medicationOption = $medicationOption;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getWaitingTimeEnd(): DateTime
    {
        return $this->waitingTimeEnd;
    }

    /**
     * @param DateTime $waitingTimeEnd
     * @return MedicationSelection
     */
    public function setWaitingTimeEnd(DateTime $waitingTimeEnd): self
    {
        $this->waitingTimeEnd = $waitingTimeEnd;

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
