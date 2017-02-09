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
 * Class NormalDistribution
 * @ORM\Entity(repositoryClass="AppBundle\Entity\NormalDistributionRepository")
 * @package AppBundle\Entity
 */
class NormalDistribution
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
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=false, options={"default":0})
     * @Assert\NotBlank
     */
    private $type;


    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $standardDeviation;


    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $mean;


    /**
     * @var boolean
     * @Assert\NotBlank
     * @ORM\Column(type="boolean", options={"default":false})
     * @JMS\Type("boolean")
     */
    private $isIncludingOnlyAliveAnimals;


    /**
     * NormalDistribution constructor.
     * @param string $type
     * @param int $year
     * @param float $standardDeviation
     * @param float $mean
     * @param boolean $isIncludingOnlyAliveAnimals
     */
    public function __construct($type, $year, $mean, $standardDeviation, $isIncludingOnlyAliveAnimals)
    {
        $this->logDate = new \DateTime();
        
        $this->type = $type;
        $this->year = $year;
        $this->standardDeviation = $standardDeviation;
        $this->mean = $mean;
        $this->isIncludingOnlyAliveAnimals = $isIncludingOnlyAliveAnimals;
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
    public function getStandardDeviation()
    {
        return $this->standardDeviation;
    }

    /**
     * @param float $standardDeviation
     */
    public function setStandardDeviation($standardDeviation)
    {
        $this->standardDeviation = $standardDeviation;
    }

    /**
     * @return float
     */
    public function getMean()
    {
        return $this->mean;
    }

    /**
     * @param float $mean
     */
    public function setMean($mean)
    {
        $this->mean = $mean;
    }

    /**
     * @return boolean
     */
    public function isIsIncludingOnlyAliveAnimals()
    {
        return $this->isIncludingOnlyAliveAnimals;
    }

    /**
     * @param boolean $isIncludingOnlyAliveAnimals
     */
    public function setIsIncludingOnlyAliveAnimals($isIncludingOnlyAliveAnimals)
    {
        $this->isIncludingOnlyAliveAnimals = $isIncludingOnlyAliveAnimals;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }




    
}