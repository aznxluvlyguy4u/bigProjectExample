<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class Ram
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RamRepository")
 * @package AppBundle\Entity
 */
class Ram extends Animal
{
  /**
   * @ORM\OneToMany(targetEntity="Animal", mappedBy="parentFather")
   * @JMS\Type("AppBundle\Entity\Ram")
   */
  public $children;



  /**
   * Ram constructor.
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
     * @return Ram
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
     * @return Ram
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
     * @return Ram
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
     * @return Ram
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
     * @return Ram
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
     * @return Ram
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
     * @return Ram
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
     * @return Ram
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
     * @return Ram
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
     * @return Ram
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
     * @return Ram
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
     * @return Ram
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
     * @return Ram
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
     * @return Ram
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

    /**
     * Set parentNeuter
     *
     * @param \AppBundle\Entity\Neuter $parentNeuter
     *
     * @return Ram
     */
    public function setParentNeuter(\AppBundle\Entity\Neuter $parentNeuter = null)
    {
        $this->parentNeuter = $parentNeuter;

        return $this;
    }

    /**
     * Get parentNeuter
     *
     * @return \AppBundle\Entity\Neuter
     */
    public function getParentNeuter()
    {
        return $this->parentNeuter;
    }
}
