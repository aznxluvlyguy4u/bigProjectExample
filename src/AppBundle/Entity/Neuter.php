<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\AnimalType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class Neuter
 * @ORM\Entity(repositoryClass="AppBundle\Entity\NeuterRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class Neuter extends Animal
{
    /**
     * @ORM\OneToMany(targetEntity="Animal", mappedBy="parentNeuter")
     * @JMS\Type("AppBundle\Entity\Neuter")
     */
    protected $children;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $objectType;

    /**
     * Neuter constructor.
     */
    public function __construct() {
        //Call super constructor first
        parent::__construct();

        $this->objectType = "Neuter";
        $this->setAnimalType(AnimalType::sheep);

        //Create children array
        $this->children = new ArrayCollection();
    }

    /**
     * Set objectType
     *
     * @param string $objectType
     *
     * @return Neuter
     */
    public function setObjectType($objectType)
    {
        $this->objectType = $objectType;

        return $this;
    }

    /**
     * Get objectType
     *
     * @return string
     */
    public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * Add child
     *
     * @param \AppBundle\Entity\Animal $child
     *
     * @return Neuter
     */
    public function addChild(\AppBundle\Entity\Animal $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param \AppBundle\Entity\Animal $child
     */
    public function removeChild(\AppBundle\Entity\Animal $child)
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
     * Set isAlive
     *
     * @param boolean $isAlive
     *
     * @return Neuter
     */
    public function setIsAlive($isAlive)
    {
        $this->isAlive = $isAlive;

        return $this;
    }

    /**
     * Get isAlive
     *
     * @return boolean
     */
    public function getIsAlive()
    {
        return $this->isAlive;
    }

    /**
     * Set birth
     *
     * @param \AppBundle\Entity\DeclareBirth $birth
     *
     * @return Neuter
     */
    public function setBirth(\AppBundle\Entity\DeclareBirth $birth = null)
    {
        $this->birth = $birth;

        return $this;
    }

    /**
     * Get birth
     *
     * @return \AppBundle\Entity\DeclareBirth
     */
    public function getBirth()
    {
        return $this->birth;
    }

    /**
     * Set ulnNumber
     *
     * @param string $ulnNumber
     *
     * @return Neuter
     */
    public function setUlnNumber($ulnNumber)
    {
        $this->ulnNumber = $ulnNumber;

        return $this;
    }

    /**
     * Set ulnCountryCode
     *
     * @param string $ulnCountryCode
     *
     * @return Neuter
     */
    public function setUlnCountryCode($ulnCountryCode)
    {
        $this->ulnCountryCode = $ulnCountryCode;

        return $this;
    }

    /**
     * Set animalOrderNumber
     *
     * @param string $animalOrderNumber
     *
     * @return Neuter
     */
    public function setAnimalOrderNumber($animalOrderNumber)
    {
        $this->animalOrderNumber = $animalOrderNumber;

        return $this;
    }

    /**
     * Remove departure
     *
     * @param \AppBundle\Entity\DeclareDepart $departure
     */
    public function removeDeparture(\AppBundle\Entity\DeclareDepart $departure)
    {
        $this->departures->removeElement($departure);
    }

    /**
     * Set surrogateMother
     *
     * @param \AppBundle\Entity\Ewe $surrogateMother
     *
     * @return Neuter
     */
    public function setSurrogateMother(\AppBundle\Entity\Ewe $surrogateMother = null)
    {
        $this->surrogateMother = $surrogateMother;

        return $this;
    }

    /**
     * Get surrogateMother
     *
     * @return \AppBundle\Entity\Ewe
     */
    public function getSurrogateMother()
    {
        return $this->surrogateMother;
    }
}
