<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\DependencyInjection\Tests\Compiler\A;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class GeneticBase
 *
 * Once a year in June, the average breedvalue for each trait in the solani file is calculated.
 * Include only breedvalues of the following animals:
 * - BreedValues for this BreedIndex should have an accuracy that is at least equal to the $minAccuracy
 * - Animals must have a year of birth that is X years before the year of the current breed values.
 *   round by year, not by date
 *   X = $geneticBaseYear
 * Both $minAccuracy and $geneticBaseYear are found in the BreedIndexCalculationTerms class.
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\GeneticBaseRepository")
 * @package AppBundle\Entity
 */
class GeneticBase
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
    private $muscleThickness;


    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $growth;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $fat;
    

    public function __construct($year = null, $muscleThickness = null, $growth = null, $fat = null)
    {
        $this->logDate = new \DateTime();
        $this->year = $year;
        $this->muscleThickness = $muscleThickness;
        $this->growth = $growth;
        $this->fat = $fat;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
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
     */
    public function setYear($year)
    {
        $this->year = $year;
    }


    /**
     * @return float
     */
    public function getMuscleThickness()
    {
        return $this->muscleThickness;
    }

    /**
     * @param float $muscleThickness
     */
    public function setMuscleThickness($muscleThickness)
    {
        $this->muscleThickness = $muscleThickness;
    }

    /**
     * @return float
     */
    public function getGrowth()
    {
        return $this->growth;
    }

    /**
     * @param float $growth
     */
    public function setGrowth($growth)
    {
        $this->growth = $growth;
    }

    /**
     * @return float
     */
    public function getFat()
    {
        return $this->fat;
    }

    /**
     * @param float $fat
     */
    public function setFat($fat)
    {
        $this->fat = $fat;
    }
    
    
    
}