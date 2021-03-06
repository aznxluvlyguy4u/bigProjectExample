<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class AnimalCache
 * @ORM\Table(name="animal_cache",indexes={@ORM\Index(name="animal_result_table_idx", columns={"animal_id"})})
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AnimalCacheRepository")
 * @package AppBundle\Entity
 */
class AnimalCache
{
    use EntityClassInfo;

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
     * @ORM\Column(type="integer", nullable=false, unique = true)
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
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $productionAge;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $litterCount;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $totalOffspringCount;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $bornAliveOffspringCount;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @Assert\NotBlank
     * @JMS\Type("boolean")
     */
    private $gaveBirthAsOneYearOld;


    /* Latest BreedValue Data */

    /**
     * @var string
     * @ORM\Column(type="string", options={"default":null}, nullable=true)
     * @JMS\Type("string")
     */
    private $breedValueLitterSize;

    /**
     * @var string
     * @ORM\Column(type="string", options={"default":"-/-"}, nullable=true)
     * @JMS\Type("string")
     */
    private $breedValueGrowth;

    /**
     * @var string
     * @ORM\Column(type="string", options={"default":"-/-"}, nullable=true)
     * @JMS\Type("string")
     */
    private $breedValueMuscleThickness;

    /**
     * @var string
     * @ORM\Column(type="string", options={"default":"-/-"}, nullable=true)
     * @JMS\Type("string")
     */
    private $breedValueFat;


    /**
     * @var string
     * @ORM\Column(type="string", options={"default":"-/-"}, nullable=true)
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

    /**
     * @var Inspector
     *
     * @ORM\ManyToOne(targetEntity="Inspector")
     * @ORM\JoinColumn(name="exterior_inspector_id", referencedColumnName="id", onDelete="set null")
     * @JMS\Type("AppBundle\Entity\Inspector")
     */
    private $exteriorInspector;

    /**
     * Bronstsynchronisatie/Bronstinductie
     *
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    private $pmsg;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $birthWeight;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $weightAt8Weeks;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $weightAt20Weeks;


    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $tailLength;


    /**
     * @var DateTime
     * @ORM\Column(type="datetime", options={"default":null}, nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $weightAt8WeeksMeasurementDate;


    /**
     * @var DateTime
     * @ORM\Column(type="datetime", options={"default":null}, nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $weightAt20WeeksMeasurementDate;


    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $ageWeightAt8Weeks;


    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $ageWeightAt20Weeks;



    public function __construct()
    {
        $this->logDate = new \DateTime();
        $this->pmsg = false;
        $this->gaveBirthAsOneYearOld = false;
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

    /**
     * @return Inspector
     */
    public function getExteriorInspector()
    {
        return $this->exteriorInspector;
    }

    /**
     * @param Inspector $exteriorInspector
     * @return AnimalCache
     */
    public function setExteriorInspector($exteriorInspector)
    {
        $this->exteriorInspector = $exteriorInspector;
        return $this;
    }

    /**
     * @return int
     */
    public function getProductionAge()
    {
        return $this->productionAge;
    }

    /**
     * @param int $productionAge
     */
    public function setProductionAge($productionAge)
    {
        $this->productionAge = $productionAge;
    }

    /**
     * @return int
     */
    public function getLitterCount()
    {
        return $this->litterCount;
    }

    /**
     * @param int $litterCount
     */
    public function setLitterCount($litterCount)
    {
        $this->litterCount = $litterCount;
    }

    /**
     * @return int
     */
    public function getTotalOffspringCount()
    {
        return $this->totalOffspringCount;
    }

    /**
     * @param int $totalOffspringCount
     */
    public function setTotalOffspringCount($totalOffspringCount)
    {
        $this->totalOffspringCount = $totalOffspringCount;
    }

    /**
     * @return int
     */
    public function getBornAliveOffspringCount()
    {
        return $this->bornAliveOffspringCount;
    }

    /**
     * @param int $bornAliveOffspringCount
     */
    public function setBornAliveOffspringCount($bornAliveOffspringCount)
    {
        $this->bornAliveOffspringCount = $bornAliveOffspringCount;
    }

    /**
     * @return boolean
     */
    public function isGaveBirthAsOneYearOld()
    {
        return $this->gaveBirthAsOneYearOld;
    }

    /**
     * @param boolean $gaveBirthAsOneYearOld
     */
    public function setGaveBirthAsOneYearOld($gaveBirthAsOneYearOld)
    {
        $this->gaveBirthAsOneYearOld = $gaveBirthAsOneYearOld;
    }

    /**
     * @return boolean
     */
    public function isPmsg()
    {
        return $this->pmsg;
    }

    /**
     * @param boolean $pmsg
     * @return AnimalCache
     */
    public function setPmsg($pmsg)
    {
        $this->pmsg = $pmsg;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getBirthWeight()
    {
        return $this->birthWeight;
    }

    /**
     * @param float|null $birthWeight
     * @return AnimalCache
     */
    public function setBirthWeight($birthWeight)
    {
        $this->birthWeight = $birthWeight;
        return $this;
    }

    /**
     * @return float
     */
    public function getWeightAt8Weeks()
    {
        return $this->weightAt8Weeks;
    }

    /**
     * @param float $weightAt8Weeks
     * @return AnimalCache
     */
    public function setWeightAt8Weeks($weightAt8Weeks)
    {
        $this->weightAt8Weeks = $weightAt8Weeks;
        return $this;
    }

    /**
     * @return float
     */
    public function getWeightAt20Weeks()
    {
        return $this->weightAt20Weeks;
    }

    /**
     * @param float $weightAt20Weeks
     * @return AnimalCache
     */
    public function setWeightAt20Weeks($weightAt20Weeks)
    {
        $this->weightAt20Weeks = $weightAt20Weeks;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getTailLength()
    {
        return $this->tailLength;
    }

    /**
     * @param float|null $tailLength
     * @return AnimalCache
     */
    public function setTailLength($tailLength)
    {
        $this->tailLength = $tailLength;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getWeightAt8WeeksMeasurementDate()
    {
        return $this->weightAt8WeeksMeasurementDate;
    }

    /**
     * @param DateTime $weightAt8WeeksMeasurementDate
     * @return AnimalCache
     */
    public function setWeightAt8WeeksMeasurementDate($weightAt8WeeksMeasurementDate)
    {
        $this->weightAt8WeeksMeasurementDate = $weightAt8WeeksMeasurementDate;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getWeightAt20WeeksMeasurementDate()
    {
        return $this->weightAt20WeeksMeasurementDate;
    }

    /**
     * @param DateTime $weightAt20WeeksMeasurementDate
     * @return AnimalCache
     */
    public function setWeightAt20WeeksMeasurementDate($weightAt20WeeksMeasurementDate)
    {
        $this->weightAt20WeeksMeasurementDate = $weightAt20WeeksMeasurementDate;
        return $this;
    }

    /**
     * @return int
     */
    public function getAgeWeightAt8Weeks()
    {
        return $this->ageWeightAt8Weeks;
    }

    /**
     * @param int $ageWeightAt8Weeks
     * @return AnimalCache
     */
    public function setAgeWeightAt8Weeks($ageWeightAt8Weeks)
    {
        $this->ageWeightAt8Weeks = $ageWeightAt8Weeks;
        return $this;
    }

    /**
     * @return int
     */
    public function getAgeWeightAt20Weeks()
    {
        return $this->ageWeightAt20Weeks;
    }

    /**
     * @param int $ageWeightAt20Weeks
     * @return AnimalCache
     */
    public function setAgeWeightAt20Weeks($ageWeightAt20Weeks)
    {
        $this->ageWeightAt20Weeks = $ageWeightAt20Weeks;
        return $this;
    }



}
