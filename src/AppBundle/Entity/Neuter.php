<?php

namespace AppBundle\Entity;

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
     * @var string
     * @JMS\Type("string")
     * @Expose
     */
    private $ulnNumber;

    /**
     * @var string
     * @JMS\Type("string")
     * @Expose
     */
    private $ulnCountryCode;

    /**
     * @var string
     * @JMS\Type("string")
     * @Expose
     */
    private $animalOrderNumber;

    /**
     * Neuter constructor.
     */
    public function __construct() {
        //Call super constructor first
        parent::__construct();

        $this->objectType = "Neuter";

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
     * @return string
     */
    public function getUlnCountryCode()
    {
        $this->ulnCountryCode = parent::getUlnCountryCode();
        return $this->ulnCountryCode;
    }

    /**
     * @return string
     */
    public function getUlnNumber()
    {
        $this->ulnNumber = parent::getUlnNumber();
        return $this->ulnNumber;
    }

    /**
     * @return string
     */
    public function getAnimalOrderNumber()
    {
        $this->animalOrderNumber = parent::getAnimalOrderNumber();
        return $this->animalOrderNumber;
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
}
