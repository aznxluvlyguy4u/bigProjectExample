<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Employee;
use AppBundle\Traits\EntityClassInfo;
use AppBundle\Util\Translation;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use \DateTime;

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
     * @var string
     * @JMS\Type("string")
     * @Assert\NotBlank
     * @ORM\Column(type="string")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE",
     *     "TREATMENT_TEMPLATE_MIN"
     * })
     */
    private $description;

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
     */
    private $dosage;

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
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return MedicationOption
     */
    public function setDescription($description)
    {
        $this->description = $description;
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



}