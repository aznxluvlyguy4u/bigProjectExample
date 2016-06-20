<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Location;
use AppBundle\Entity\Animal;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \DateTime;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class AnimalResidence
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AnimalResidenceRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class AnimalResidence
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     */
    private $id;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @Expose
     */
    private $startDate;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @Expose
     */
    private $endDate;

    /**
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="animalResidenceHistory")
     * @JMS\Type("AppBundle\Entity\Animal")
     * @Expose
     */
    private $animal;

    /**
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="animalResidenceHistory")
     * @JMS\Type("AppBundle\Entity\Location")
     * @Expose
     */
    private $location;

    /**
     * @var boolean
     * @Assert\NotBlank
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     * @Expose
     */
    private $isPending;

    /**
     * AnimalResidence constructor.
     */
    public function __construct()
    {
        $this->isPending = true;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param DateTime $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * @return DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param DateTime $endDate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
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
     * @return boolean
     */
    public function isIsPending()
    {
        return $this->isPending;
    }

    /**
     * @param boolean $isPending
     */
    public function setIsPending($isPending)
    {
        $this->isPending = $isPending;
    }



    /**
     * Get isPending
     *
     * @return boolean
     */
    public function getIsPending()
    {
        return $this->isPending;
    }
}
