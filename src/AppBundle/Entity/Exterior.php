<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Location
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ExteriorRepository")
 * @package AppBundle\Entity
 */
class Exterior extends Measurement
{
    use EntityClassInfo;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 3)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     */
    private $kind;


    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     * @Assert\NotBlank
     */
    private $skull;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     * @Assert\NotBlank
     */
    private $muscularity;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     * @Assert\NotBlank
     */
    private $proportion;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     */
    private $progress;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     * @Assert\NotBlank
     */
    private $exteriorType;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     * @Assert\NotBlank
     */
    private $legWork;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     * @Assert\NotBlank
     */
    private $fur;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     * @Assert\NotBlank
     */
    private $generalAppearance;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     * @Assert\NotBlank
     */
    private $height;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     * @Assert\NotBlank
     */
    private $breastDepth;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     * @Assert\NotBlank
     */
    private $torsoLength;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     * @Assert\NotBlank
     */
    private $markings;

    /**
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="exteriorMeasurements")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    public function __construct() {
      parent::__construct();
      $this->skull = 0.00;
      $this->muscularity = 0.00;
      $this->proportion = 0.00;
      $this->exteriorType = 0.00;
      $this->legWork = 0.00;
      $this->fur = 0.00;
      $this->generalAppearance = 0.00;
      $this->height = 0.00;
      $this->breastDepth = 0.00;
      $this->torsoLength = 0.00;
      $this->markings = 0.00;
      $this->progress = 0.00;
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
     * @param float $minLimit
     * @return bool
     */
    public function hasAnyValuesAbove($minLimit)
    {
        return
        $this->skull > $minLimit ||
        $this->muscularity > $minLimit ||
        $this->proportion > $minLimit ||
        $this->exteriorType > $minLimit ||
        $this->legWork > $minLimit ||
        $this->fur > $minLimit ||
        $this->generalAppearance > $minLimit ||
        $this->height > $minLimit ||
        $this->breastDepth > $minLimit ||
        $this->torsoLength > $minLimit ||
        $this->markings > $minLimit ||
        $this->progress > $minLimit;
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
     *
     * @return Exterior
     */
    public function setKind($kind)
    {
        $this->kind = $kind;

        return $this;
    }

    /**
     * Set skull
     *
     * @param float $skull
     *
     * @return Exterior
     */
    public function setSkull($skull)
    {
        $this->skull = $skull;

        return $this;
    }

    /**
     * Get skull
     *
     * @return float
     */
    public function getSkull()
    {
        return $this->skull;
    }

    /**
     * Set muscularity
     *
     * @param float $muscularity
     *
     * @return Exterior
     */
    public function setMuscularity($muscularity)
    {
        $this->muscularity = $muscularity;

        return $this;
    }

    /**
     * Get muscularity
     *
     * @return float
     */
    public function getMuscularity()
    {
        return $this->muscularity;
    }

    /**
     * Set proportion
     *
     * @param float $proportion
     *
     * @return Exterior
     */
    public function setProportion($proportion)
    {
        $this->proportion = $proportion;

        return $this;
    }

    /**
     * Get proportion
     *
     * @return float
     */
    public function getProportion()
    {
        return $this->proportion;
    }

    /**
     * @return Exterior
     *
     * @param float $progress
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;

        return $this;
    }

    /**
     * @return float
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * Set legWork
     *
     * @param float $legWork
     *
     * @return Exterior
     */
    public function setLegWork($legWork)
    {
        $this->legWork = $legWork;

        return $this;
    }

    /**
     * Get legWork
     *
     * @return float
     */
    public function getLegWork()
    {
        return $this->legWork;
    }

    /**
     * Set fur
     *
     * @param float $fur
     *
     * @return Exterior
     */
    public function setFur($fur)
    {
        $this->fur = $fur;

        return $this;
    }

    /**
     * Get fur
     *
     * @return float
     */
    public function getFur()
    {
        return $this->fur;
    }

    /**
     * Set generalAppearance
     *
     * @param float $generalAppearance
     *
     * @return Exterior
     */
    public function setGeneralAppearance($generalAppearance)
    {
        $this->generalAppearance = $generalAppearance;

        return $this;
    }

    /**
     * Get generalAppearance
     *
     * @return float
     */
    public function getGeneralAppearance()
    {
        return $this->generalAppearance;
    }

    /**
     * Set height
     *
     * @param float $height
     *
     * @return Exterior
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get height
     *
     * @return float
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set breastDepth
     *
     * @param float $breastDepth
     *
     * @return Exterior
     */
    public function setBreastDepth($breastDepth)
    {
        $this->breastDepth = $breastDepth;

        return $this;
    }

    /**
     * Get breastDepth
     *
     * @return float
     */
    public function getBreastDepth()
    {
        return $this->breastDepth;
    }

    /**
     * Set torsoLength
     *
     * @param float $torsoLength
     *
     * @return Exterior
     */
    public function setTorsoLength($torsoLength)
    {
        $this->torsoLength = $torsoLength;

        return $this;
    }

    /**
     * Get torsoLength
     *
     * @return float
     */
    public function getTorsoLength()
    {
        return $this->torsoLength;
    }

    /**
     * Set markings
     *
     * @param float $markings
     *
     * @return Exterior
     */
    public function setMarkings($markings)
    {
        $this->markings = $markings;

        return $this;
    }

    /**
     * Get markings
     *
     * @return float
     */
    public function getMarkings()
    {
        return $this->markings;
    }

    /**
     * Set animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return Exterior
     */
    public function setAnimal(\AppBundle\Entity\Animal $animal = null)
    {
        $this->animal = $animal;

        return $this;
    }

    /**
     * Get animal
     *
     * @return \AppBundle\Entity\Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * Set exteriorType
     *
     * @param float $exteriorType
     *
     * @return Exterior
     */
    public function setExteriorType($exteriorType)
    {
        $this->exteriorType = $exteriorType;

        return $this;
    }

    /**
     * Get exteriorType
     *
     * @return float
     */
    public function getExteriorType()
    {
        return $this->exteriorType;
    }

    /**
     * Set inspector
     *
     * @param \AppBundle\Entity\Inspector $inspector
     *
     * @return Exterior
     */
    public function setInspector(\AppBundle\Entity\Inspector $inspector = null)
    {
        $this->inspector = $inspector;

        return $this;
    }

    /**
     * Get inspector
     *
     * @return \AppBundle\Entity\Inspector
     */
    public function getInspector()
    {
        return $this->inspector;
    }
}
