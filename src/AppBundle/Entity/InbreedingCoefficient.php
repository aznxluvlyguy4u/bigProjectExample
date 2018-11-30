<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Inspector
 * @ORM\Entity(repositoryClass="AppBundle\Entity\InbreedingCoefficientRepository")
 * @package AppBundle\Entity
 */
class InbreedingCoefficient
{
    use EntityClassInfo;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Ram
     * @ORM\ManyToOne(targetEntity="Ram", inversedBy="inbreedingCoefficients")
     * @ORM\JoinColumn(name="ram_id", referencedColumnName="id", onDelete="CASCADE")
     * @Assert\NotBlank
     */
    private $ram;

    /**
     * @var Ewe
     * @ORM\ManyToOne(targetEntity="Ewe", inversedBy="inbreedingCoefficients")
     * @ORM\JoinColumn(name="ewe_id", referencedColumnName="id", onDelete="CASCADE")
     * @Assert\NotBlank
     */
    private $ewe;

    /**
     * @var float
     *
     * @ORM\Column(type="float", nullable=false, options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $value;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return InbreedingCoefficient
     */
    public function setId(int $id): InbreedingCoefficient
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Ram
     */
    public function getRam(): Ram
    {
        return $this->ram;
    }

    /**
     * @param Ram $ram
     * @return InbreedingCoefficient
     */
    public function setRam(Ram $ram): InbreedingCoefficient
    {
        $this->ram = $ram;
        return $this;
    }

    /**
     * @return Ewe
     */
    public function getEwe(): Ewe
    {
        return $this->ewe;
    }

    /**
     * @param Ewe $ewe
     * @return InbreedingCoefficient
     */
    public function setEwe(Ewe $ewe): InbreedingCoefficient
    {
        $this->ewe = $ewe;
        return $this;
    }

    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * @param float $value
     * @return InbreedingCoefficient
     */
    public function setValue(float $value): InbreedingCoefficient
    {
        $this->value = $value;
        return $this;
    }


}