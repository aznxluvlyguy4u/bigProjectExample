<?php

namespace AppBundle\Entity;
use DateTime;
use AppBundle\Entity\Animal;
use AppBundle\Entity\LocationHealthInspection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class LocationHealthInspection
 * @ORM\Entity(repositoryClass="AppBundle\Entity\LocationHealthInspectionResultRepository")
 * @package AppBundle\Entity
 */
class LocationHealthInspectionResult
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMS\Exclude
     */
    protected $id;

    /**
     * @var LocationHealthInspection
     *
     * @ORM\ManyToOne(targetEntity="LocationHealthInspection", inversedBy="results")
     * @JMS\Type("AppBundle\Entity\LocationHealthInspection")
     */
    private $inspection;

    /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="Animal")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $customerSampleId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $mgxSampleId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $genotype;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $genotypeWithCondon;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $genotypeClass;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $vetName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $subRef;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $mvnp;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $mvCAEPool;

    /**
     * @var datetime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $receptionDate;

    /**
     * @var datetime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $resultDate;

    /**
     * @var datetime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * LocationHealthInspectionResult constructor.
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
     * @return LocationHealthInspection
     */
    public function getInspection()
    {
        return $this->inspection;
    }

    /**
     * @param LocationHealthInspection $inspection
     */
    public function setInspection($inspection)
    {
        $this->inspection = $inspection;
    }

    /**
     * @return Location
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * @param Location $animal
     */
    public function setAnimal($animal)
    {
        $this->animal = $animal;
    }

    /**
     * @return string
     */
    public function getCustomerSampleId()
    {
        return $this->customerSampleId;
    }

    /**
     * @param string $customerSampleId
     */
    public function setCustomerSampleId($customerSampleId)
    {
        $this->customerSampleId = $customerSampleId;
    }

    /**
     * @return string
     */
    public function getMgxSampleId()
    {
        return $this->mgxSampleId;
    }

    /**
     * @param string $mgxSampleId
     */
    public function setMgxSampleId($mgxSampleId)
    {
        $this->mgxSampleId = $mgxSampleId;
    }

    /**
     * @return string
     */
    public function getGenotype()
    {
        return $this->genotype;
    }

    /**
     * @param string $genotype
     */
    public function setGenotype($genotype)
    {
        $this->genotype = $genotype;
    }

    /**
     * @return string
     */
    public function getGenotypeWithCondon()
    {
        return $this->genotypeWithCondon;
    }

    /**
     * @param string $genotypeWithCondon
     */
    public function setGenotypeWithCondon($genotypeWithCondon)
    {
        $this->genotypeWithCondon = $genotypeWithCondon;
    }

    /**
     * @return string
     */
    public function getGenotypeClass()
    {
        return $this->genotypeClass;
    }

    /**
     * @param string $genotypeClass
     */
    public function setGenotypeClass($genotypeClass)
    {
        $this->genotypeClass = $genotypeClass;
    }

    /**
     * @return string
     */
    public function getVetName()
    {
        return $this->vetName;
    }

    /**
     * @param string $vetName
     */
    public function setVetName($vetName)
    {
        $this->vetName = $vetName;
    }

    /**
     * @return string
     */
    public function getSubRef()
    {
        return $this->subRef;
    }

    /**
     * @param string $subRef
     */
    public function setSubRef($subRef)
    {
        $this->subRef = $subRef;
    }

    /**
     * @return string
     */
    public function getMvnp()
    {
        return $this->mvnp;
    }

    /**
     * @param string $mvnp
     */
    public function setMvnp($mvnp)
    {
        $this->mvnp = $mvnp;
    }

    /**
     * @return string
     */
    public function getMvCAEPool()
    {
        return $this->mvCAEPool;
    }

    /**
     * @param string $mvCAEPool
     */
    public function setMvCAEPool($mvCAEPool)
    {
        $this->mvCAEPool = $mvCAEPool;
    }

    /**
     * @return DateTime
     */
    public function getReceptionDate()
    {
        return $this->receptionDate;
    }

    /**
     * @param DateTime $receptionDate
     */
    public function setReceptionDate($receptionDate)
    {
        $this->receptionDate = $receptionDate;
    }

    /**
     * @return DateTime
     */
    public function getResultDate()
    {
        return $this->resultDate;
    }

    /**
     * @param DateTime $resultDate
     */
    public function setResultDate($resultDate)
    {
        $this->resultDate = $resultDate;
    }

    /**
     * @return DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * @param DateTime $logDate
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
    }
}