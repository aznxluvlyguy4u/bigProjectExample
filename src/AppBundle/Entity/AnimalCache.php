<?php


namespace AppBundle\Entity;

use AppBundle\Entity\Animal;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \DateTime;

/**
 * Class AnimalCache
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AnimalCacheRepository")
 * @package AppBundle\Entity
 */
class AnimalCache
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    protected $logDate;

    /**
     * @ORM\OneToOne(targetEntity="Animal")
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id")
     */
    private $animal;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $dutchBreedStatus;


    /* Latest Litter Data */

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $nLing;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $production;


    /* Latest BreedValue Data */

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $breedValueLitterSize;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $breedValueGrowth;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $breedValueMuscleThickness;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $breedValueFat;


    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $lambMeatIndex;


    /* Latest Weight Data */

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $lastWeight;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $weightMeasurementDate;


    /* Latest Exterior Measurement Data */

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $kind;


    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $skull;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $muscularity;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $proportion;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $progress;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $exteriorType;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $legWork;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $fur;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $generalAppearance;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $height;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $breastDepth;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $torsoLength;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $markings;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $exteriorMeasurementDate;


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
     * @return mixed
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * @param mixed $animal
     */
    public function setAnimal($animal)
    {
        $this->animal = $animal;
    }

    /**
     * @return string
     */
    public function getDutchBreedStatus()
    {
        return $this->dutchBreedStatus;
    }

    /**
     * @param string $dutchBreedStatus
     */
    public function setDutchBreedStatus($dutchBreedStatus)
    {
        $this->dutchBreedStatus = $dutchBreedStatus;
    }

    /**
     * @return string
     */
    public function getNLing()
    {
        return $this->nLing;
    }

    /**
     * @param string $nLing
     */
    public function setNLing($nLing)
    {
        $this->nLing = $nLing;
    }

    /**
     * @return string
     */
    public function getProduction()
    {
        return $this->production;
    }

    /**
     * @param string $production
     */
    public function setProduction($production)
    {
        $this->production = $production;
    }

    /**
     * @return string
     */
    public function getBreedValueLitterSize()
    {
        return $this->breedValueLitterSize;
    }

    /**
     * @param string $breedValueLitterSize
     */
    public function setBreedValueLitterSize($breedValueLitterSize)
    {
        $this->breedValueLitterSize = $breedValueLitterSize;
    }

    /**
     * @return string
     */
    public function getBreedValueGrowth()
    {
        return $this->breedValueGrowth;
    }

    /**
     * @param string $breedValueGrowth
     */
    public function setBreedValueGrowth($breedValueGrowth)
    {
        $this->breedValueGrowth = $breedValueGrowth;
    }

    /**
     * @return string
     */
    public function getBreedValueMuscleThickness()
    {
        return $this->breedValueMuscleThickness;
    }

    /**
     * @param string $breedValueMuscleThickness
     */
    public function setBreedValueMuscleThickness($breedValueMuscleThickness)
    {
        $this->breedValueMuscleThickness = $breedValueMuscleThickness;
    }

    /**
     * @return string
     */
    public function getBreedValueFat()
    {
        return $this->breedValueFat;
    }

    /**
     * @param string $breedValueFat
     */
    public function setBreedValueFat($breedValueFat)
    {
        $this->breedValueFat = $breedValueFat;
    }

    /**
     * @return string
     */
    public function getLambMeatIndex()
    {
        return $this->lambMeatIndex;
    }

    /**
     * @param string $lambMeatIndex
     */
    public function setLambMeatIndex($lambMeatIndex)
    {
        $this->lambMeatIndex = $lambMeatIndex;
    }

    /**
     * @return string
     */
    public function getLastWeight()
    {
        return $this->lastWeight;
    }

    /**
     * @param string $lastWeight
     */
    public function setLastWeight($lastWeight)
    {
        $this->lastWeight = $lastWeight;
    }

    /**
     * @return DateTime
     */
    public function getWeightMeasurementDate()
    {
        return $this->weightMeasurementDate;
    }

    /**
     * @param DateTime $weightMeasurementDate
     */
    public function setWeightMeasurementDate($weightMeasurementDate)
    {
        $this->weightMeasurementDate = $weightMeasurementDate;
    }

    /**
     * @return string
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * @param string $kind
     */
    public function setKind($kind)
    {
        $this->kind = $kind;
    }

    /**
     * @return string
     */
    public function getSkull()
    {
        return $this->skull;
    }

    /**
     * @param string $skull
     */
    public function setSkull($skull)
    {
        $this->skull = $skull;
    }

    /**
     * @return string
     */
    public function getMuscularity()
    {
        return $this->muscularity;
    }

    /**
     * @param string $muscularity
     */
    public function setMuscularity($muscularity)
    {
        $this->muscularity = $muscularity;
    }

    /**
     * @return string
     */
    public function getProportion()
    {
        return $this->proportion;
    }

    /**
     * @param string $proportion
     */
    public function setProportion($proportion)
    {
        $this->proportion = $proportion;
    }

    /**
     * @return string
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * @param string $progress
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;
    }

    /**
     * @return string
     */
    public function getExteriorType()
    {
        return $this->exteriorType;
    }

    /**
     * @param string $exteriorType
     */
    public function setExteriorType($exteriorType)
    {
        $this->exteriorType = $exteriorType;
    }

    /**
     * @return string
     */
    public function getLegWork()
    {
        return $this->legWork;
    }

    /**
     * @param string $legWork
     */
    public function setLegWork($legWork)
    {
        $this->legWork = $legWork;
    }

    /**
     * @return string
     */
    public function getFur()
    {
        return $this->fur;
    }

    /**
     * @param string $fur
     */
    public function setFur($fur)
    {
        $this->fur = $fur;
    }

    /**
     * @return string
     */
    public function getGeneralAppearance()
    {
        return $this->generalAppearance;
    }

    /**
     * @param string $generalAppearance
     */
    public function setGeneralAppearance($generalAppearance)
    {
        $this->generalAppearance = $generalAppearance;
    }

    /**
     * @return string
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param string $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @return string
     */
    public function getBreastDepth()
    {
        return $this->breastDepth;
    }

    /**
     * @param string $breastDepth
     */
    public function setBreastDepth($breastDepth)
    {
        $this->breastDepth = $breastDepth;
    }

    /**
     * @return string
     */
    public function getTorsoLength()
    {
        return $this->torsoLength;
    }

    /**
     * @param string $torsoLength
     */
    public function setTorsoLength($torsoLength)
    {
        $this->torsoLength = $torsoLength;
    }

    /**
     * @return string
     */
    public function getMarkings()
    {
        return $this->markings;
    }

    /**
     * @param string $markings
     */
    public function setMarkings($markings)
    {
        $this->markings = $markings;
    }

    /**
     * @return DateTime
     */
    public function getExteriorMeasurementDate()
    {
        return $this->exteriorMeasurementDate;
    }

    /**
     * @param DateTime $exteriorMeasurementDate
     */
    public function setExteriorMeasurementDate($exteriorMeasurementDate)
    {
        $this->exteriorMeasurementDate = $exteriorMeasurementDate;
    }



}