<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class BreedValueGeneticBase
 *
 * Once a year in June, the average breedvalue for each trait in the solani file is calculated.
 * Include only breedvalues of the following animals:
 * - BreedValues for this BreedIndex should have an accuracy that is at least equal to the $minAccuracy
 * - Animals must have a year of birth that is X years before the year of the current breed values.
 *   round by year, not by date
 *   X = $geneticBaseYear
 * Both $minAccuracy and $geneticBaseYear are found in the BreedIndexCalculationTerms class.
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\BreedValueGeneticBaseRepository")
 * @package AppBundle\Entity
 */
class BreedValueGeneticBase
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
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     * @JMS\Type("integer")
     */
    private $year;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $value;

    /**
     * @var BreedValueType
     * @ORM\ManyToOne(targetEntity="BreedValueType")
     * @ORM\JoinColumn(name="breed_value_type_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValueType")
     * @JMS\Groups({"MIXBLUP"})
     */
    private $breedValueType;

    /**
     * BreedValueGeneticBase constructor.
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
     * @return BreedValueGeneticBase
     */
    public function setId($id)
    {
        $this->id = $id;
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
     * @return BreedValueGeneticBase
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
        return $this;
    }

    /**
     * @return int
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * @param int $year
     * @return BreedValueGeneticBase
     */
    public function setYear($year)
    {
        $this->year = $year;
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
     * @return BreedValueGeneticBase
     */
    public function setValue($value)
    {
        $this->value = $value;
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
     * @return BreedValueGeneticBase
     */
    public function setBreedValueType($breedValueType)
    {
        $this->breedValueType = $breedValueType;
        return $this;
    }


}