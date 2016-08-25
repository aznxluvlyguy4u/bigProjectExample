<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class DeclareWeight
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareWeightRepository")
 * @package AppBundle\Entity
 */
class DeclareWeight extends DeclareNsfoBase
{
    /**
     * @var Animal
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy = "declareWeights", cascade={"persist"})
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $measurementDate;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $weight;

    /**
     * @var boolean
     * @Assert\NotBlank
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     */
    private $isBirthWeight;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="DeclareWeight", mappedBy="currentVersion", cascade={"persist"})
     */
    private $previousVersions;

    /**
     * @var DeclareWeight
     * @ORM\ManyToOne(targetEntity="DeclareWeight", inversedBy="previousVersions", cascade={"persist"})
     * @ORM\JoinColumn(name="current_version_id", referencedColumnName="id")
     */
    private $currentVersion;

    /**
     * @var Location
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="declareWeights", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Location")
     */
    private $location;

    /**
     * @var Weight
     * @ORM\ManyToOne(targetEntity="Weight", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Weight")
     */
    private $weightMeasurement;

    /**
     * DeclareWeight constructor.
     * @param bool $isBirthWeight
     */
    public function __construct($isBirthWeight = false)
    {
        parent::__construct();

        $this->isBirthWeight = $isBirthWeight;
        $this->weight = 0.00;
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
     */
    public function setAnimal($animal)
    {
        $this->animal = $animal;
    }

    /**
     * @return DateTime
     */
    public function getMeasurementDate()
    {
        return $this->measurementDate;
    }

    /**
     * @param DateTime $measurementDate
     */
    public function setMeasurementDate($measurementDate)
    {
        $this->measurementDate = $measurementDate;
    }

    /**
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * @return boolean
     */
    public function getIsBirthWeight()
    {
        return $this->isBirthWeight;
    }

    /**
     * @param boolean $isBirthWeight
     */
    public function setIsBirthWeight($isBirthWeight)
    {
        $this->isBirthWeight = $isBirthWeight;
    }

    /**
     * @return ArrayCollection
     */
    public function getPreviousVersions()
    {
        return $this->previousVersions;
    }

    /**
     * @param ArrayCollection $previousVersions
     */
    public function setPreviousVersions($previousVersions)
    {
        $this->previousVersions = $previousVersions;
    }

    /**
     * @return DeclareWeight
     */
    public function getCurrentVersion()
    {
        return $this->currentVersion;
    }

    /**
     * @param DeclareWeight $currentVersion
     */
    public function setCurrentVersion($currentVersion)
    {
        $this->currentVersion = $currentVersion;
    }

    /**
     * Add a previousVersion DeclareWeight
     *
     * @param DeclareWeight $previousVersion
     *
     * @return DeclareWeight
     */
    public function addPreviousVersion(DeclareWeight $previousVersion)
    {
        $this->previousVersions[] = $previousVersion;
    }

    /**
     * Remove a previousVersion DeclareWeight
     *
     * @param DeclareWeight $previousVersion
     */
    public function removePreviousVersion(DeclareWeight $previousVersion)
    {
        $this->previousVersions->removeElement($previousVersion);
    }

    /**
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param Location $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * @return Weight
     */
    public function getWeightMeasurement()
    {
        return $this->weightMeasurement;
    }

    /**
     * @param Weight $weightMeasurement
     */
    public function setWeightMeasurement($weightMeasurement)
    {
        $this->weightMeasurement = $weightMeasurement;
    }


    /**
     * @param DeclareWeight $declareWeight
     */
    public function duplicateValues(DeclareWeight $declareWeight)
    {
        //Note 'currentVersion' and 'previousVersions' are not duplicated. They set the history relationship.
        //The OneToMany reference is used to group them.
        parent::duplicateBaseValues($declareWeight);

        //Mate specific values
        $this->setMeasurementDate($declareWeight->getMeasurementDate());
        $this->setAnimal($declareWeight->getAnimal());
        $this->setWeight($declareWeight->getWeight());
        $this->setIsBirthWeight($declareWeight->getIsBirthWeight());
        $this->setLocation($declareWeight->getLocation());

        //NOTE the Weight entity is not duplicated! Only the reference to the Weight entity is.
        $this->setWeightMeasurement($declareWeight->getWeightMeasurement());
    }

}