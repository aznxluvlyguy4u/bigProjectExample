<?php


namespace AppBundle\Entity;

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
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", options={"default":"CURRENT_TIMESTAMP"}, nullable=true)
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    protected $logDate;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=false)
     * @Assert\NotBlank
     * @JMS\Type("integer")
     */
    private $animalId;

    /**
     * @var string
     * @ORM\Column(type="string", options={"default":null}, nullable=true)
     * @JMS\Type("string")
     */
    private $dutchBreedStatus;
    
    /**
     * @var string
     * @ORM\Column(type="string", options={"default":null}, nullable=true)
     * @JMS\Type("string")
     */
    private $predicate;

    /* Latest Litter Data */

    /**
     * @var string
     * @ORM\Column(type="string", options={"default":null}, nullable=true)
     * @JMS\Type("string")
     */
    private $nLing;

    /**
     * @var string
     * @ORM\Column(type="string", options={"default":"-/-/-/-"}, nullable=true)
     * @JMS\Type("string")
     */
    private $production;


    /* Latest BreedValue Data */

    /**
     * @var string
     * @ORM\Column(type="string", options={"default":null}, nullable=true)
     * @JMS\Type("string")
     */
    private $breedValueLitterSize;

    /**
     * @var string
     * @ORM\Column(type="string", options={"default":null}, nullable=true)
     * @JMS\Type("string")
     */
    private $breedValueGrowth;

    /**
     * @var string
     * @ORM\Column(type="string", options={"default":null}, nullable=true)
     * @JMS\Type("string")
     */
    private $breedValueMuscleThickness;

    /**
     * @var string
     * @ORM\Column(type="string", options={"default":null}, nullable=true)
     * @JMS\Type("string")
     */
    private $breedValueFat;


    /**
     * @var string
     * @ORM\Column(type="string", options={"default":null}, nullable=true)
     * @JMS\Type("string")
     */
    private $lambMeatIndex;


    /**
     * NOTE! Only include the lambIndexValue if the accuracy is at least the MIN accuracy required
     * 
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $lambMeatIndexWithoutAccuracy;


    /* Latest Weight Data */

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $lastWeight;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", options={"default":null}, nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $weightMeasurementDate;


    /* Latest Exterior Measurement Data */

    /**
     * @var string
     * @ORM\Column(type="string", options={"default":null}, nullable=true)
     * @JMS\Type("string")
     */
    private $kind;


    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $skull;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $muscularity;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $proportion;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $progress;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $exteriorType;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $legWork;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $fur;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $generalAppearance;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $height;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $breastDepth;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $torsoLength;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $markings;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", options={"default":null}, nullable=true)
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

    /**
     * @return int
     */
    public function getAnimalId()
    {
        return $this->animalId;
    }

    /**
     * @param int $animalId
     */
    public function setAnimalId($animalId)
    {
        $this->animalId = $animalId;
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
    public function getPredicate()
    {
        return $this->predicate;
    }

    /**
     * @param string $predicate
     */
    public function setPredicate($predicate)
    {
        $this->predicate = $predicate;
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
     * @return float
     */
    public function getLambMeatIndexWithoutAccuracy()
    {
        return $this->lambMeatIndexWithoutAccuracy;
    }

    /**
     * @param float $lambMeatIndexWithoutAccuracy
     */
    public function setLambMeatIndexWithoutAccuracy($lambMeatIndexWithoutAccuracy)
    {
        $this->lambMeatIndexWithoutAccuracy = $lambMeatIndexWithoutAccuracy;
    }

    /**
     * @return float
     */
    public function getLastWeight()
    {
        return $this->lastWeight;
    }

    /**
     * @param float $lastWeight
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
     * @param string $weightMeasurementDateString
     */
    public function setWeightMeasurementDateByDateString($weightMeasurementDateString)
    {
        $this->weightMeasurementDate = new DateTime($weightMeasurementDateString);
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
     * @return float
     */
    public function getSkull()
    {
        return $this->skull;
    }

    /**
     * @param float $skull
     */
    public function setSkull($skull)
    {
        $this->skull = $skull;
    }

    /**
     * @return float
     */
    public function getMuscularity()
    {
        return $this->muscularity;
    }

    /**
     * @param float $muscularity
     */
    public function setMuscularity($muscularity)
    {
        $this->muscularity = $muscularity;
    }

    /**
     * @return float
     */
    public function getProportion()
    {
        return $this->proportion;
    }

    /**
     * @param float $proportion
     */
    public function setProportion($proportion)
    {
        $this->proportion = $proportion;
    }

    /**
     * @return float
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * @param float $progress
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;
    }

    /**
     * @return float
     */
    public function getExteriorType()
    {
        return $this->exteriorType;
    }

    /**
     * @param float $exteriorType
     */
    public function setExteriorType($exteriorType)
    {
        $this->exteriorType = $exteriorType;
    }

    /**
     * @return float
     */
    public function getLegWork()
    {
        return $this->legWork;
    }

    /**
     * @param float $legWork
     */
    public function setLegWork($legWork)
    {
        $this->legWork = $legWork;
    }

    /**
     * @return float
     */
    public function getFur()
    {
        return $this->fur;
    }

    /**
     * @param float $fur
     */
    public function setFur($fur)
    {
        $this->fur = $fur;
    }

    /**
     * @return float
     */
    public function getGeneralAppearance()
    {
        return $this->generalAppearance;
    }

    /**
     * @param float $generalAppearance
     */
    public function setGeneralAppearance($generalAppearance)
    {
        $this->generalAppearance = $generalAppearance;
    }

    /**
     * @return float
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param float $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @return float
     */
    public function getBreastDepth()
    {
        return $this->breastDepth;
    }

    /**
     * @param float $breastDepth
     */
    public function setBreastDepth($breastDepth)
    {
        $this->breastDepth = $breastDepth;
    }

    /**
     * @return float
     */
    public function getTorsoLength()
    {
        return $this->torsoLength;
    }

    /**
     * @param float $torsoLength
     */
    public function setTorsoLength($torsoLength)
    {
        $this->torsoLength = $torsoLength;
    }

    /**
     * @return float
     */
    public function getMarkings()
    {
        return $this->markings;
    }

    /**
     * @param float $markings
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
    
    /**
     * @param string $exteriorMeasurementDateString
     */
    public function setExteriorMeasurementDateByDateString($exteriorMeasurementDateString)
    {
        $this->exteriorMeasurementDate = new \DateTime($exteriorMeasurementDateString);
    }
    
}