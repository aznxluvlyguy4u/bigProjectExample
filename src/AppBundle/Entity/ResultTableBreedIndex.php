<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ResultTableBreedIndex
 *
 * Table saving the latest BreedIndexValues
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity")
 * @package AppBundle\Entity
 */
class ResultTableBreedIndex
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
     * @ORM\Column(type="datetime", options={"default":"CURRENT_TIMESTAMP"}, nullable=true)
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @var Animal
     * @ORM\OneToOne(targetEntity="Animal", inversedBy="latestBreedIndices")
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id", nullable=false)
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $lambMeatIndex;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $lambMeatAccuracy;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $exteriorIndex;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $exteriorAccuracy;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $fertilityIndex;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $fertilityAccuracy;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $wormResistanceIndex;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $wormResistanceAccuracy;

    /**
     * ResultTableBreedIndex constructor.
     */
    public function __construct()
    {
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
     * @return ResultTableBreedIndex
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
     * @return ResultTableBreedIndex
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
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
     * @return ResultTableBreedIndex
     */
    public function setAnimal($animal)
    {
        $this->animal = $animal;
        return $this;
    }

    /**
     * @return float
     */
    public function getLambMeatIndex()
    {
        return $this->lambMeatIndex;
    }

    /**
     * @param float $lambMeatIndex
     * @return ResultTableBreedIndex
     */
    public function setLambMeatIndex($lambMeatIndex)
    {
        $this->lambMeatIndex = $lambMeatIndex;
        return $this;
    }

    /**
     * @return float
     */
    public function getLambMeatAccuracy()
    {
        return $this->lambMeatAccuracy;
    }

    /**
     * @param float $lambMeatAccuracy
     * @return ResultTableBreedIndex
     */
    public function setLambMeatAccuracy($lambMeatAccuracy)
    {
        $this->lambMeatAccuracy = $lambMeatAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getExteriorIndex()
    {
        return $this->exteriorIndex;
    }

    /**
     * @param float $exteriorIndex
     * @return ResultTableBreedIndex
     */
    public function setExteriorIndex($exteriorIndex)
    {
        $this->exteriorIndex = $exteriorIndex;
        return $this;
    }

    /**
     * @return float
     */
    public function getExteriorAccuracy()
    {
        return $this->exteriorAccuracy;
    }

    /**
     * @param float $exteriorAccuracy
     * @return ResultTableBreedIndex
     */
    public function setExteriorAccuracy($exteriorAccuracy)
    {
        $this->exteriorAccuracy = $exteriorAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getFertilityIndex()
    {
        return $this->fertilityIndex;
    }

    /**
     * @param float $fertilityIndex
     * @return ResultTableBreedIndex
     */
    public function setFertilityIndex($fertilityIndex)
    {
        $this->fertilityIndex = $fertilityIndex;
        return $this;
    }

    /**
     * @return float
     */
    public function getFertilityAccuracy()
    {
        return $this->fertilityAccuracy;
    }

    /**
     * @param float $fertilityAccuracy
     * @return ResultTableBreedIndex
     */
    public function setFertilityAccuracy($fertilityAccuracy)
    {
        $this->fertilityAccuracy = $fertilityAccuracy;
        return $this;
    }

    /**
     * @return float
     */
    public function getWormResistanceIndex()
    {
        return $this->wormResistanceIndex;
    }

    /**
     * @param float $wormResistanceIndex
     * @return ResultTableBreedIndex
     */
    public function setWormResistanceIndex($wormResistanceIndex)
    {
        $this->wormResistanceIndex = $wormResistanceIndex;
        return $this;
    }

    /**
     * @return float
     */
    public function getWormResistanceAccuracy()
    {
        return $this->wormResistanceAccuracy;
    }

    /**
     * @param float $wormResistanceAccuracy
     * @return ResultTableBreedIndex
     */
    public function setWormResistanceAccuracy($wormResistanceAccuracy)
    {
        $this->wormResistanceAccuracy = $wormResistanceAccuracy;
        return $this;
    }


}