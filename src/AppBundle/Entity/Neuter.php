<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class Neuter
 * @ORM\Entity(repositoryClass="AppBundle\Entity\NeuterRepository")
 * @package AppBundle\Entity
 */
class Neuter extends Animal
{
    //FIXME Should a neuter have registered children
    /**
     * @ORM\OneToMany(targetEntity="Animal", mappedBy="parentNeuter")
     * @JMS\Type("AppBundle\Entity\Neuter")
     */
    public $children;


    /**
     * Neuter constructor.
     */
    public function __construct() {
        //Call super constructor first
        parent::__construct();

        //Create children array
        $this->children = new ArrayCollection();
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
     * Set pedigreeCountryCode
     *
     * @param string $pedigreeCountryCode
     *
     * @return Neuter
     */
    public function setPedigreeCountryCode($pedigreeCountryCode)
    {
        $this->pedigreeCountryCode = $pedigreeCountryCode;

        return $this;
    }

    /**
     * Get pedigreeCountryCode
     *
     * @return string
     */
    public function getPedigreeCountryCode()
    {
        return $this->pedigreeCountryCode;
    }

    /**
     * Set pedigreeNumber
     *
     * @param string $pedigreeNumber
     *
     * @return Neuter
     */
    public function setPedigreeNumber($pedigreeNumber)
    {
        $this->pedigreeNumber = $pedigreeNumber;

        return $this;
    }

    /**
     * Get pedigreeNumber
     *
     * @return string
     */
    public function getPedigreeNumber()
    {
        return $this->pedigreeNumber;
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
     * Get ulnCountryCode
     *
     * @return string
     */
    public function getUlnCountryCode()
    {
        return $this->ulnCountryCode;
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
     * Get ulnNumber
     *
     * @return string
     */
    public function getUlnNumber()
    {
        return $this->ulnNumber;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Neuter
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set dateOfBirth
     *
     * @param \DateTime $dateOfBirth
     *
     * @return Neuter
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    /**
     * Get dateOfBirth
     *
     * @return \DateTime
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }

    /**
     * Set dateOfDeath
     *
     * @param \DateTime $dateOfDeath
     *
     * @return Neuter
     */
    public function setDateOfDeath($dateOfDeath)
    {
        $this->dateOfDeath = $dateOfDeath;

        return $this;
    }

    /**
     * Get dateOfDeath
     *
     * @return \DateTime
     */
    public function getDateOfDeath()
    {
        return $this->dateOfDeath;
    }

    /**
     * Set gender
     *
     * @param string $gender
     *
     * @return Neuter
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set animalType
     *
     * @param integer $animalType
     *
     * @return Neuter
     */
    public function setAnimalType($animalType)
    {
        $this->animalType = $animalType;

        return $this;
    }

    /**
     * Get animalType
     *
     * @return integer
     */
    public function getAnimalType()
    {
        return $this->animalType;
    }

    /**
     * Set animalCategory
     *
     * @param integer $animalCategory
     *
     * @return Neuter
     */
    public function setAnimalCategory($animalCategory)
    {
        $this->animalCategory = $animalCategory;

        return $this;
    }

    /**
     * Get animalCategory
     *
     * @return integer
     */
    public function getAnimalCategory()
    {
        return $this->animalCategory;
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
     * Set parentFather
     *
     * @param \AppBundle\Entity\Ram $parentFather
     *
     * @return Neuter
     */
    public function setParentFather(\AppBundle\Entity\Ram $parentFather = null)
    {
        $this->parentFather = $parentFather;

        return $this;
    }

    /**
     * Get parentFather
     *
     * @return \AppBundle\Entity\Ram
     */
    public function getParentFather()
    {
        return $this->parentFather;
    }

    /**
     * Set parentMother
     *
     * @param \AppBundle\Entity\Ewe $parentMother
     *
     * @return Neuter
     */
    public function setParentMother(\AppBundle\Entity\Ewe $parentMother = null)
    {
        $this->parentMother = $parentMother;

        return $this;
    }

    /**
     * Get parentMother
     *
     * @return \AppBundle\Entity\Ewe
     */
    public function getParentMother()
    {
        return $this->parentMother;
    }

    /**
     * Add arrival
     *
     * @param \AppBundle\Entity\DeclareArrival $arrival
     *
     * @return Neuter
     */
    public function addArrival(\AppBundle\Entity\DeclareArrival $arrival)
    {
        $this->arrivals[] = $arrival;

        return $this;
    }

    /**
     * Remove arrival
     *
     * @param \AppBundle\Entity\DeclareArrival $arrival
     */
    public function removeArrival(\AppBundle\Entity\DeclareArrival $arrival)
    {
        $this->arrivals->removeElement($arrival);
    }

    /**
     * Get arrivals
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getArrivals()
    {
        return $this->arrivals;
    }
}
