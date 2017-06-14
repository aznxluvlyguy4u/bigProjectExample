<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class BreedValue
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\BreedValueRepository")
 * @package AppBundle\Entity
 */
class BreedValue
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Animal")
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $generationDate;


    /**
     * Solani value
     *
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $value;

    /**
     * Relani value
     *
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $reliability;


    /**
     * @var BreedValueType
     * @ORM\ManyToOne(targetEntity="BreedValueType")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValueType")
     * @Assert\NotBlank
     */
    private $type;


    public function __construct()
    {
        $this->logDate = new \DateTime();
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
     * @return BreedValue
     */
    public function setId($id)
    {
        $this->id = $id;
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
     * @return BreedValue
     */
    public function setAnimal($animal)
    {
        $this->animal = $animal;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * @param \DateTime $logDate
     * @return BreedValue
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getGenerationDate()
    {
        return $this->generationDate;
    }

    /**
     * @param \DateTime $generationDate
     * @return BreedValue
     */
    public function setGenerationDate($generationDate)
    {
        $this->generationDate = $generationDate;
        return $this;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     * @return BreedValue
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return float
     */
    public function getReliability()
    {
        return $this->reliability;
    }

    /**
     * @param float $reliability
     * @return BreedValue
     */
    public function setReliability($reliability)
    {
        $this->reliability = $reliability;
        return $this;
    }

    /**
     * @return BreedValueType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param BreedValueType $type
     * @return BreedValue
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }


    /**
     * @return null|string
     */
    public function getBreedValueTypeEn()
    {
        return $this->type != null ? $this->type->getEn() : null;
    }

    /**
     * @return null|string
     */
    public function getBreedValueTypeNl()
    {
        return $this->type != null ? $this->type->getNl() : null;
    }
}