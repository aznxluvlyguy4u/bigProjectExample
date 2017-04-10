<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Animal;
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
    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({"ANIMAL_DETAILS", "BASIC"})
     */
    private $litterDate;

    /**
     * @ORM\ManyToOne(targetEntity="Ram", inversedBy="litters")
     * @ORM\JoinColumn(name="animal_father_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Ram")
     * @JMS\Groups({"ANIMAL_DETAILS", "PARENTS"})
     * @JMS\MaxDepth(depth=2)
     */
    private $animalFather;

    /**
     * @ORM\ManyToOne(targetEntity="Ewe", inversedBy="litters")
     * @ORM\JoinColumn(name="animal_mother_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Ewe")
     * @JMS\Groups({"ANIMAL_DETAILS", "PARENTS"})
     * @JMS\MaxDepth(depth=2)
     */
    private $animalMother;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $litterGroup;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", options={"default":0})
     * @JMS\Type("integer")
     * @JMS\Groups({"ANIMAL_DETAILS", "BASIC"})
     */
    private $stillbornCount;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", options={"default":0})
     * @JMS\Type("integer")
     * @JMS\Groups({"ANIMAL_DETAILS", "BASIC"})
     */
    private $bornAliveCount;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @JMS\Groups({"ANIMAL_DETAILS", "BASIC"})
     */
    private $isAbortion;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @JMS\Groups({"ANIMAL_DETAILS", "BASIC"})
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
     */
    private $declareBirths;

    /**
     * @ORM\Column(type="string", options={"default": "INCOMPLETE"})
     * @JMS\Type("string")
     * @JMS\Groups({"ANIMAL_DETAILS", "BASIC"})
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
     * @return string
     */
    public function getLitterGroup()
    {
        return $this->litterGroup;
    }

    /**
     * @param string $litterGroup
     */
    public function setLitterGroup($litterGroup)
    {
        $this->litterGroup = $litterGroup;
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
     * @param Animal $child
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
     * @param \AppBundle\Entity\DeclareBirth $birth
     *
     * @return Animal
     */
    public function addDeclareBirth(\AppBundle\Entity\DeclareBirth $declareBirth)
    {
        $this->declareBirths[] = $declareBirth;

        return $this;
    }

    /**
     * Remove declareBirth
     *
     * @param \AppBundle\Entity\DeclareBirth $birth
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
}
