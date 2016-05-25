<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\RequestStateType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareAnimalFlag
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareAnimalFlagRepository")
 * @package AppBundle\Entity
 */
class DeclareAnimalFlag extends DeclareBase
{
    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="flags", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Location")
     */
    private $location;

    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="flags", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 10)
     * @JMS\Type("string")
     */
    private $flagType;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $flagStartDate;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $flagEndDate;

    /**
     * @ORM\OneToMany(targetEntity="DeclareAnimalFlagResponse", mappedBy="declareAnimalFlagRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="declare_animal_flag_request_message_id", referencedColumnName="id")
     * @JMS\Type("array")
     */
    private $responses;

    public function __construct() {
      parent::__construct();

      $this->setRequestState(RequestStateType::OPEN);
      $this->responses = new ArrayCollection();
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
     * @param \DateTime $flagEndDate
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
     * @return \DateTime
     */
    public function getFlagEndDate()
    {
        return $this->flagEndDate;
    }

    /**
     * Set location
     *
     * @param \AppBundle\Entity\Location $location
     *
     * @return DeclareAnimalFlag
     */
    public function setLocation(\AppBundle\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return \AppBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return DeclareAnimalFlag
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
     * Add response
     *
     * @param \AppBundle\Entity\DeclareAnimalFlagResponse $response
     *
     * @return DeclareAnimalFlag
     */
    public function addResponse(\AppBundle\Entity\DeclareAnimalFlagResponse $response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Remove response
     *
     * @param \AppBundle\Entity\DeclareAnimalFlagResponse $response
     */
    public function removeResponse(\AppBundle\Entity\DeclareAnimalFlagResponse $response)
    {
        $this->responses->removeElement($response);
    }

    /**
     * Get responses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getResponses()
    {
        return $this->responses;
    }
}
