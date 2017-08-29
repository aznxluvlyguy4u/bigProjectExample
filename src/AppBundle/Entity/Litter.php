<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Animal;
use AppBundle\Traits\EntityClassInfo;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Litter
 * @ORM\Entity(repositoryClass="AppBundle\Entity\LitterRepository")
 * @package AppBundle\Entity
 */
class Litter extends DeclareNsfoBase
{
    use EntityClassInfo;

    /**
     * @ORM\OneToOne(targetEntity="Mate")
     * @ORM\JoinColumn(name="mate_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Mate")
     */
    private $mate;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({"ANIMAL_DETAILS","BASIC","ERROR_DETAILS"})
     */
    private $litterDate;

    /**
     * @var Ram
     * @ORM\ManyToOne(targetEntity="Ram", inversedBy="litters")
     * @ORM\JoinColumn(name="animal_father_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Ram")
     * @JMS\Groups({"ANIMAL_DETAILS","ERROR_DETAILS","PARENTS"})
     * @JMS\MaxDepth(depth=2)
     */
    private $animalFather;

    /**
     * @var Ewe
     * @ORM\ManyToOne(targetEntity="Ewe", inversedBy="litters")
     * @ORM\JoinColumn(name="animal_mother_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Ewe")
     * @JMS\Groups({"ANIMAL_DETAILS","ERROR_DETAILS","PARENTS"})
     * @JMS\MaxDepth(depth=2)
     */
    private $animalMother;

    /**
     * The number designating then place in an ordered sequence of litters for a specific ewe
     * starting at 1.
     *
     * @var integer
     * @ORM\Column(type="integer", nullable=true, options={"default":null})
     * @JMS\Type("integer")
     */
    private $litterOrdinal;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", options={"default":0})
     * @JMS\Type("integer")
     * @JMS\Groups({"ANIMAL_DETAILS","BASIC","ERROR_DETAILS"})
     */
    private $stillbornCount;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", options={"default":0})
     * @JMS\Type("integer")
     * @JMS\Groups({"ANIMAL_DETAILS","BASIC","ERROR_DETAILS"})
     */
    private $bornAliveCount;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @JMS\Groups({"ANIMAL_DETAILS","BASIC","ERROR_DETAILS"})
     */
    private $isAbortion;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @JMS\Groups({"ANIMAL_DETAILS","BASIC","ERROR_DETAILS"})
     */
    private $isPseudoPregnancy;

    /**
     * @ORM\OneToMany(targetEntity="Animal", mappedBy="litter", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $children;

    /**
     * @var ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="Stillborn", mappedBy="litter", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Stillborn")
     */
    private $stillborns;

    /**
     * @var ArrayCollection
     * @JMS\Type("AppBundle\Entity\DeclareBirth")
     * @ORM\OneToMany(targetEntity="DeclareBirth", mappedBy="litter", cascade={"persist"})
     * @JMS\Groups({"ERROR_DETAILS"})
     */
    private $declareBirths;

    /**
     * @ORM\Column(type="string", options={"default": "INCOMPLETE"})
     * @JMS\Type("string")
     * @JMS\Groups({"ANIMAL_DETAILS","BASIC","ERROR_DETAILS"})
     */
    private $status;

    /**
     * Get size
     *
     * @JMS\VirtualProperty
     * @JMS\SerializedName("n_ling")
     * @JMS\Groups({"ANIMAL_DETAILS", "BASIC"})
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->stillbornCount + $this->bornAliveCount;
    }

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true, options={"default":null})
     * @JMS\Type("integer")
     */
    private $suckleCount;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true, options={"default":null})
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $suckleCountUpdateDate;

    /**
     * Time in days the fetus has been inside the womb of the mother
     * Dutch: draagtijd
     *
     * @var integer
     * @ORM\Column(type="integer", nullable=true, options={"default":null})
     * @JMS\Type("integer")
     */
    private $gestationPeriod;

    /**
     * Time in days between litterDate/dateOfBirth of this animal and the previous litterDate.
     * If this is the first litter, then this birthInterval is null
     * Dutch: tussenlamtijd
     *
     * @var integer
     * @ORM\Column(type="integer", nullable=true, options={"default":null})
     * @JMS\Type("integer")
     */
    private $birthInterval;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $heterosis;

    /**
     * @var float
     * @ORM\Column(type="float", options={"default":null}, nullable=true)
     * @JMS\Type("float")
     */
    private $recombination;

    /**
     * @var boolean
     * @JMS\Type("boolean")
     * @ORM\Column(type="boolean", options={"default":false}, nullable=false)
     */
    private $updatedGeneDiversity;

    /**
     * Litter constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->children = new ArrayCollection();
        $this->stillborns = new ArrayCollection();
        $this->logDate = new \DateTime();
        $this->stillbornCount = 0;
        $this->bornAliveCount = 0;
        $this->declareBirths = new ArrayCollection();
        $this->updatedGeneDiversity = false;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Mate
     */
    public function getMate()
    {
        return $this->mate;
    }

    /**
     * @param Mate $mate
     * @return Litter
     */
    public function setMate($mate)
    {
        $this->mate = $mate;
        return $this;
    }

    /**
     * Set logDate
     *
     * @param \DateTime $logDate
     *
     * @return Litter
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;

        return $this;
    }

    /**
     * Get logDate
     *
     * @return \DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * Set litterDate
     *
     * @param \DateTime $litterDate
     *
     * @return Litter
     */
    public function setLitterDate($litterDate)
    {
        $this->litterDate = $litterDate;

        return $this;
    }

    /**
     * Get litterDate
     *
     * @return \DateTime
     */
    public function getLitterDate()
    {
        return $this->litterDate;
    }

    /**
     * @return integer
     */
    public function getLitterOrdinal()
    {
        return $this->litterOrdinal;
    }

    /**
     * @param integer $litterOrdinal
     */
    public function setLitterOrdinal($litterOrdinal)
    {
        $this->litterOrdinal = $litterOrdinal;
    }

    /**
     * @return int
     */
    public function getStillbornCount()
    {
        return $this->stillbornCount;
    }

    /**
     * @param int $stillbornCount
     */
    public function setStillbornCount($stillbornCount)
    {
        $this->stillbornCount = $stillbornCount;
    }

    /**
     * @return int
     */
    public function getBornAliveCount()
    {
        return $this->bornAliveCount;
    }

    /**
     * @param int $bornAliveCount
     */
    public function setBornAliveCount($bornAliveCount)
    {
        $this->bornAliveCount = $bornAliveCount;
    }

    /**
     * Set animalFather
     *
     * @param \AppBundle\Entity\Animal $animalFather
     *
     * @return Litter
     */
    public function setAnimalFather(\AppBundle\Entity\Animal $animalFather = null)
    {
        $this->animalFather = $animalFather;

        return $this;
    }

    /**
     * Get animalFather
     *
     * @return \AppBundle\Entity\Animal
     */
    public function getAnimalFather()
    {
        return $this->animalFather;
    }

    /**
     * Set animalMother
     *
     * @param \AppBundle\Entity\Animal $animalMother
     *
     * @return Litter
     */
    public function setAnimalMother(\AppBundle\Entity\Animal $animalMother = null)
    {
        $this->animalMother = $animalMother;

        return $this;
    }

    /**
     * Get animalMother
     *
     * @return \AppBundle\Entity\Animal
     */
    public function getAnimalMother()
    {
        return $this->animalMother;
    }

    /**
     * Add child
     *
     * @param Animal $child
     *
     * @return Litter
     */
    public function addChild(Animal $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param Animal $child
     */
    public function removeChild(Animal $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return mixed
     */
    public function getAbortion()
    {
        return $this->isAbortion;
    }

    /**
     * @param mixed $isAbortion
     */
    public function setIsAbortion($isAbortion)
    {
        $this->isAbortion = $isAbortion;
    }

    /**
     * @return mixed
     */
    public function getPseudoPregnancy()
    {
        return $this->isPseudoPregnancy;
    }

    /**
     * @param mixed $isPseudoPregnancy
     */
    public function setIsPseudoPregnancy($isPseudoPregnancy)
    {
        $this->isPseudoPregnancy = $isPseudoPregnancy;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Add Stillborn
     *
     * @param Stillborn $stillborn
     *
     * @return Litter
     */
    public function addStillborn(Stillborn $stillborn)
    {
        $this->stillborns[] = $stillborn;

        return $this;
    }

    /**
     * Remove Stillborn
     *
     * @param Stillborn $stillborn
     */
    public function removeStillborn(Stillborn $stillborn)
    {
        $this->stillborns->removeElement($stillborn);
    }

    /**
     * Get Stillborns
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getStillborns()
    {
        return $this->stillborns;
    }

    /**
     * Add declareBirth
     *
     * @param \AppBundle\Entity\DeclareBirth $declareBirth
     *
     * @return Litter
     */
    public function addDeclareBirth(\AppBundle\Entity\DeclareBirth $declareBirth)
    {
        $this->declareBirths[] = $declareBirth;

        return $this;
    }

    /**
     * Remove declareBirth
     *
     * @param \AppBundle\Entity\DeclareBirth $declareBirth
     */
    public function removeDeclareBirth(\AppBundle\Entity\DeclareBirth $declareBirth)
    {
        $this->declareBirths->removeElement($declareBirth);
    }

    /**
     * Get declareBirths
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDeclareBirths()
    {
        return $this->declareBirths;
    }

    /**
     * @return int
     */
    public function getSuckleCount()
    {
        return $this->suckleCount;
    }


    /**
     * @param int $suckleCount
     * @return Litter
     */
    public function setSuckleCount($suckleCount)
    {
        $this->suckleCount = $suckleCount;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getSuckleCountUpdateDate()
    {
        return $this->suckleCountUpdateDate;
    }

    /**
     * @param DateTime $suckleCountUpdateDate
     * @return Litter
     */
    public function setSuckleCountUpdateDate($suckleCountUpdateDate)
    {
        $this->suckleCountUpdateDate = $suckleCountUpdateDate;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getGestationPeriod()
    {
        return $this->gestationPeriod;
    }

    /**
     * @param int|null $gestationPeriod
     * @return Litter
     */
    public function setGestationPeriod($gestationPeriod)
    {
        $this->gestationPeriod = $gestationPeriod;
        return $this;
    }

    /**
     * @return int
     */
    public function getBirthInterval()
    {
        return $this->birthInterval;
    }

    /**
     * @param int $birthInterval
     * @return Litter
     */
    public function setBirthInterval($birthInterval)
    {
        $this->birthInterval = $birthInterval;
        return $this;
    }

    /**
     * @return float
     */
    public function getHeterosis()
    {
        return $this->heterosis;
    }

    /**
     * @param float $heterosis
     * @return Litter
     */
    public function setHeterosis($heterosis)
    {
        $this->heterosis = $heterosis;
        return $this;
    }

    /**
     * @return float
     */
    public function getRecombination()
    {
        return $this->recombination;
    }

    /**
     * @param float $recombination
     * @return Litter
     */
    public function setRecombination($recombination)
    {
        $this->recombination = $recombination;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isUpdatedGeneDiversity()
    {
        return $this->updatedGeneDiversity;
    }

    /**
     * @param boolean $updatedGeneDiversity
     * @return Litter
     */
    public function setUpdatedGeneDiversity($updatedGeneDiversity)
    {
        $this->updatedGeneDiversity = $updatedGeneDiversity;
        return $this;
    }
    

    /**
     * @return string
     */
    public function getLitterGroup()
    {
        $mother = $this->getAnimalMother();
        if($mother && $this->getLitterOrdinal()) {
            $uln = $mother->getUln();
            if($uln) {
                $paddedLitterOrdinal = str_pad($this->getLitterOrdinal(), 2, "0", STR_PAD_LEFT);
                return $uln.'_'.$paddedLitterOrdinal;
            }
        }
        return null;
    }


}
