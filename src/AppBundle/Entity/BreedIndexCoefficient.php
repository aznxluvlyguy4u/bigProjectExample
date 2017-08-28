<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class BreedIndexCoefficient
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\BreedIndexCoefficientRepository")
 * @package AppBundle\Entity
 */
class BreedIndexCoefficient
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Groups({"MIXBLUP"})
     */
    private $id;

    /**
     * @var BreedIndexType
     * @ORM\ManyToOne(targetEntity="BreedIndexType")
     * @ORM\JoinColumn(name="breed_index_type_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedIndexType")
     * @JMS\Groups({"MIXBLUP"})
     */
    private $breedIndexType;

    /**
     * @var BreedValueType
     * @ORM\ManyToOne(targetEntity="BreedValueType")
     * @ORM\JoinColumn(name="breed_value_type_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValueType")
     * @JMS\Groups({"MIXBLUP"})
     */
    private $breedValueType;

    /**
     * Coefficient C for breedIndex and breedValue pair
     *
     * @var float
     * @Assert\NotBlank
     * @ORM\Column(type="float", options={"default":1})
     * @JMS\Type("float")
     * @JMS\Groups({"MIXBLUP"})
     */
    private $c;

    /**
     * Coefficient var (genetic variance) for breedIndex and breedValue pair
     *
     * @var float
     * @Assert\NotBlank
     * @ORM\Column(type="float", options={"default":1})
     * @JMS\Type("float")
     * @JMS\Groups({"MIXBLUP"})
     */
    private $var;

    /**
     * Coefficient T for breedIndex and breedValue pair
     *
     * @var float
     * @Assert\NotBlank
     * @ORM\Column(type="float", options={"default":1})
     * @JMS\Type("float")
     * @JMS\Groups({"MIXBLUP"})
     */
    private $t;

    /**
     * @var \DateTime
     *
     * @Assert\Date
     * @Assert\NotBlank
     * @ORM\Column(type="datetime")
     * @JMS\Type("DateTime")
     * @JMS\Groups({"MIXBLUP"})
     */
    private $logDate;

    /**
     * @var \DateTime
     *
     * @Assert\Date
     * @Assert\NotBlank
     * @ORM\Column(type="datetime")
     * @JMS\Type("DateTime")
     * @JMS\Groups({"MIXBLUP"})
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @Assert\Date
     * @ORM\Column(type="datetime", nullable=true)
     * @JMS\Type("DateTime")
     * @JMS\Groups({"MIXBLUP"})
     */
    private $endDate;

    /**
     * BreedIndexCoefficient constructor.
     */
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
     * @return BreedIndexCoefficient
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return BreedIndexType
     */
    public function getBreedIndexType()
    {
        return $this->breedIndexType;
    }

    /**
     * @param BreedIndexType $breedIndexType
     * @return BreedIndexCoefficient
     */
    public function setBreedIndexType($breedIndexType)
    {
        $this->breedIndexType = $breedIndexType;
        return $this;
    }

    /**
     * @return BreedValueType
     */
    public function getBreedValueType()
    {
        return $this->breedValueType;
    }

    /**
     * @param BreedValueType $breedValueType
     * @return BreedIndexCoefficient
     */
    public function setBreedValueType($breedValueType)
    {
        $this->breedValueType = $breedValueType;
        return $this;
    }

    /**
     * @return float
     */
    public function getC()
    {
        return $this->c;
    }

    /**
     * @param float $c
     * @return BreedIndexCoefficient
     */
    public function setC($c)
    {
        $this->c = $c;
        return $this;
    }

    /**
     * @return float
     */
    public function getVar()
    {
        return $this->var;
    }

    /**
     * @param float $var
     * @return BreedIndexCoefficient
     */
    public function setVar($var)
    {
        $this->var = $var;
        return $this;
    }

    /**
     * @return float
     */
    public function getT()
    {
        return $this->t;
    }

    /**
     * @param float $t
     * @return BreedIndexCoefficient
     */
    public function setT($t)
    {
        $this->t = $t;
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
     * @return BreedIndexCoefficient
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime $startDate
     * @return BreedIndexCoefficient
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param \DateTime $endDate
     * @return BreedIndexCoefficient
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
        return $this;
    }



}