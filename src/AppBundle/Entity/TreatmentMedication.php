<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
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

}
