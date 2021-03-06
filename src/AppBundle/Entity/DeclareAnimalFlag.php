<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * RVO name: "Vastleggen Diervlagmelding".
 * What it is: Register the flagging of an animal.
 *
 * Class DeclareAnimalFlag
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareAnimalFlagRepository")
 * @package AppBundle\Entity
 */
class DeclareAnimalFlag extends DeclareBaseWithResponse
{
    use EntityClassInfo;

    /**
     * @var Location
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="flags", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Location")
     * @JMS\Groups({
     *     "RVO"
     * })
     */
    private $location;

    /**
     * @var Animal
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="flags", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Animal")
     * @JMS\Groups({
     *     "RVO"
     * })
     */
    private $animal;

    /**
     * @var Treatment
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Treatment", inversedBy="declareAnimalFlags", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Treatment")
     */
    private $treatment;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 10)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "RVO",
     *     "TREATMENT"
     * })
     */
    private $flagType;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "RVO",
     *     "TREATMENT"
     * })
     */
    private $flagStartDate;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "RVO",
     *     "TREATMENT"
     * })
     */
    private $flagEndDate;

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("uln")
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "TREATMENT"
     * })
     * @return string
     */
    public function getUln(): string {
        return $this->animal ? $this->animal->getUln(): '';
    }

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("animal_id")
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "TREATMENT"
     * })
     * @return int|null
     */
    public function getAnimalId(): ?int {
        return $this->animal ? $this->animal->getId(): null;
    }


    /**
     * Set flagType
     *
     * @param string $flagType
     *
     * @return DeclareAnimalFlag
     */
    public function setFlagType($flagType)
    {
        $this->flagType = $flagType;

        return $this;
    }

    /**
     * Get flagType
     *
     * @return string
     */
    public function getFlagType()
    {
        return $this->flagType;
    }

    /**
     * Set flagStartDate
     *
     * @param \DateTime $flagStartDate
     *
     * @return DeclareAnimalFlag
     */
    public function setFlagStartDate($flagStartDate)
    {
        $this->flagStartDate = $flagStartDate;

        return $this;
    }

    /**
     * Get flagStartDate
     *
     * @return \DateTime
     */
    public function getFlagStartDate()
    {
        return $this->flagStartDate;
    }

    /**
     * Set flagEndDate
     *
     * @param \DateTime|null $flagEndDate
     *
     * @return DeclareAnimalFlag
     */
    public function setFlagEndDate($flagEndDate)
    {
        $this->flagEndDate = $flagEndDate;

        return $this;
    }

    /**
     * Get flagEndDate
     *
     * @return \DateTime|null
     */
    public function getFlagEndDate()
    {
        return $this->flagEndDate;
    }

    /**
     * Set location
     *
     * @param Location $location
     *
     * @return DeclareAnimalFlag
     */
    public function setLocation(Location $location = null)
    {
        $this->location = $location;
        $this->setUbn($location ? $location->getUbn() : null);

        return $this;
    }

    /**
     * Get location
     *
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set animal
     *
     * @param Animal $animal
     *
     * @return DeclareAnimalFlag
     */
    public function setAnimal(Animal $animal = null)
    {
        $this->animal = $animal;

        return $this;
    }

    /**
     * Get animal
     *
     * @return Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }


    /**
     * @return Treatment
     */
    public function getTreatment(): Treatment
    {
        return $this->treatment;
    }

    /**
     * @param  Treatment  $treatment
     * @return DeclareAnimalFlag
     */
    public function setTreatment(Treatment $treatment): DeclareAnimalFlag
    {
        $this->treatment = $treatment;
        return $this;
    }


}
