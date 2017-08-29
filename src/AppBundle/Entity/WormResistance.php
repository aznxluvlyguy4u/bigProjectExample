<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class WormResistance
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\WormResistanceRepository")
 * @package AppBundle\Entity
 */
class WormResistance
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
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", options={"default":"CURRENT_TIMESTAMP"}, nullable=false)
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $samplingDate;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="action_by_id", referencedColumnName="id")
     */
    private $actionBy;

    /**
     * @var Animal
     *
     * @ORM\ManyToOne(targetEntity="Animal")
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id")
     */
    private $animal;

    /**
     * @var int
     * @Assert\NotBlank
     * @JMS\Type("integer")
     * @ORM\Column(type="integer", nullable=false)
     */
    private $year;


    /**
     * Behandeld voor monsteren
     *
     * @var boolean
     * @JMS\Type("boolean")
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $treatedForSamples;


    /**
     * EPG
     *
     * @var float
     * @JMS\Type("float")
     * @ORM\Column(type="float", nullable=true)
     */
    private $epg;


    /**
     * SIgA Glasgow (% from pos)
     *
     * @var float
     * @JMS\Type("float")
     * @ORM\Column(type="float", nullable=true)
     */
    private $sIgaGlasgow;

    /**
     * CARLA IgA NZ
     *
     * @var float
     * @JMS\Type("float")
     * @ORM\Column(type="float", nullable=true)
     */
    private $carlaIgaNz;


    /**
     * Class CARLA IgA NZ
     *
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     */
    private $classCarlaIgaNz;


    /**
     * @var int
     * @JMS\Type("integer")
     * @ORM\Column(type="integer", nullable=true)
     */
    private $samplePeriod;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     */
    private $notes;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":true})
     * @JMS\Type("boolean")
     */
    private $isActive;

    /**
     * WormResistance constructor.
     */
    public function __construct()
    {
        $this->logDate = new \DateTime();
        $this->isActive = true;
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
     * @return WormResistance
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
     * @return WormResistance
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSamplingDate()
    {
        return $this->samplingDate;
    }

    /**
     * @param \DateTime $samplingDate
     */
    public function setSamplingDate($samplingDate)
    {
        $this->samplingDate = $samplingDate;
    }

    /**
     * @return Person
     */
    public function getActionBy()
    {
        return $this->actionBy;
    }

    /**
     * @param Person $actionBy
     * @return WormResistance
     */
    public function setActionBy($actionBy)
    {
        $this->actionBy = $actionBy;
        return $this;
    }

    /**
     * @return Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * @param Animal $animal
     * @return WormResistance
     */
    public function setAnimal($animal)
    {
        $this->animal = $animal;
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
     * @return WormResistance
     */
    public function setYear($year)
    {
        $this->year = $year;
        return $this;
    }

    /**
     * @return bool
     */
    public function isTreatedForSamples()
    {
        return $this->treatedForSamples;
    }

    /**
     * @param bool $treatedForSamples
     * @return WormResistance
     */
    public function setTreatedForSamples($treatedForSamples)
    {
        $this->treatedForSamples = $treatedForSamples;
        return $this;
    }

    /**
     * @return float
     */
    public function getEpg()
    {
        return $this->epg;
    }

    /**
     * @param float $epg
     * @return WormResistance
     */
    public function setEpg($epg)
    {
        $this->epg = $epg;
        return $this;
    }

    /**
     * @return float
     */
    public function getSIgaGlasgow()
    {
        return $this->sIgaGlasgow;
    }

    /**
     * @param float $sIgaGlasgow
     * @return WormResistance
     */
    public function setSIgaGlasgow($sIgaGlasgow)
    {
        $this->sIgaGlasgow = $sIgaGlasgow;
        return $this;
    }

    /**
     * @return float
     */
    public function getCarlaIgaNz()
    {
        return $this->carlaIgaNz;
    }

    /**
     * @param float $carlaIgaNz
     * @return WormResistance
     */
    public function setCarlaIgaNz($carlaIgaNz)
    {
        $this->carlaIgaNz = $carlaIgaNz;
        return $this;
    }

    /**
     * @return string
     */
    public function getClassCarlaIgaNz()
    {
        return $this->classCarlaIgaNz;
    }

    /**
     * @param string $classCarlaIgaNz
     * @return WormResistance
     */
    public function setClassCarlaIgaNz($classCarlaIgaNz)
    {
        $this->classCarlaIgaNz = $classCarlaIgaNz;
        return $this;
    }

    /**
     * @return int
     */
    public function getSamplePeriod()
    {
        return $this->samplePeriod;
    }

    /**
     * @param int $samplePeriod
     * @return WormResistance
     */
    public function setSamplePeriod($samplePeriod)
    {
        $this->samplePeriod = $samplePeriod;
        return $this;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     * @return WormResistance
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
        return $this;
    }


    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * @param bool $isActive
     * @return WormResistance
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
        return $this;
    }


}